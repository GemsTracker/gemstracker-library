<?php


class OpenRosaFormAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = array('onlyUsedElements' => true);

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Token\\EditTokenSnippet';

    /**
     *
     * @var \Zend_Locale
     */
    public $locale;

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $token = $this->getToken();
        $model = $token->getSurveyAnswerModel($this->locale->getLanguage());

        $items = $model->getItemNames();
        $model->remove('token', 'label');
        $model->set('orf_id', 'elementClass', 'Hidden');

        if ($model->getMeta('nested', false)) {
            // We have a nested model, add the nested questions
            $nestedNames  = $model->getMeta('nestedNames');
            $requiredRows = array();
            for ($i=1; $i<21; $i++) {
                $row = array('orfr_id' => $i);
                $requiredRows[] = $row;
            }
            $transformer = new \MUtil\Model\Transform\RequiredRowsTransformer();
            $transformer->setRequiredRows($requiredRows);

            foreach($nestedNames as $nestedName) {
                $nestedModel = $model->get($nestedName, 'model');
                $nestedModel->addTransformer($transformer);
            }
        }

        return $model;
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return $string
     */
    public function getEditTitle()
    {
        return $this->_('Fill in');
    }

    /**
     * Retrieve the token
     *
     * @return \Gems\Tracker\Token
     */
    public function getToken()
    {
        static $token;

        if ($token instanceof \Gems\Tracker\Token) {
            return $token;
        }

        $token   = null;
        $tokenId = $this->getTokenId();

        if ($tokenId) {
            $token = $this->loader->getTracker()->getToken($tokenId);
        }
        if ($token && $token->exists) {
            // Set variables for the menu
            $token->applyToMenuSource($this->menu->getParameterSource());

            return $token;
        }

        throw new \Gems\Exception($this->_('No existing token specified!'));
    }

    /**
     * Retrieve the token ID
     *
     * @return string
     */
    public function getTokenId()
    {
        return $this->_getIdParam();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);
    }

}