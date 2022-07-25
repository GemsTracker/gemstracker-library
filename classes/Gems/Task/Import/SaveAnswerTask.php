<?php

/**
 *
 * @package    Gems
 * @subpackage SaveAnswerTask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Import;

/**
 *
 * @package    Gems
 * @subpackage SaveAnswerTask
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class SaveAnswerTask extends \MUtil\Task\TaskAbstract
{    
    /**
     *
     * @var \Iterator
     */
    protected $iterator;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \MUtil\Model\ModelTranslatorInterface
     */
    protected $modelTranslator;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $targetModel;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->targetModel instanceof \MUtil\Model\ModelAbstract) &&
                parent::checkRegistryRequestsAnswers();
    }

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param array $row Row to save
     */
    public function execute($row = null,
            $noToken = \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR,
            $tokenCompletion = \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR)
    {
        // \MUtil\EchoOut\EchoOut::track($row);
        if ($this->iterator instanceof \Iterator && !is_array($row) && is_int($row)) {
            $key = $row;
            if ($key < $this->iterator->key()) { $this->iterator->rewind(); }
            while ($key > $this->iterator->key()) {
                $this->iterator->next();
            }
            $current = $this->iterator->current();
            $row     = $this->modelTranslator->translateRowValues($current, $key);

            if ($row) {
                $row = $this->modelTranslator->validateRowValues($row, $key);
            }
            $errors = $this->modelTranslator->getRowErrors($key);
        }
        if ($row) {
            $answers     = $row;
            $prevAnswers = false;
            $token       = null;
            $tracker     = $this->loader->getTracker();
            $userId      = $this->loader->getCurrentUser()->getUserId();

            // \Gems\Tracker::$verbose = true;

            $batch = $this->getBatch();
            $batch->addToCounter('imported');

            // Remove all "non-answer" fields
            unset($answers['token'], $answers['patient_id'], $answers['organization_id'], $answers['track_id'], 
                    $answers['survey_id'], $answers['completion_date'], $answers['gto_id_token']);

            if (isset($row['survey_id'])) {
                $model = $this->targetModel;
                foreach ($answers as $key => &$value) {
                    if ($value instanceof \Zend_Date) {
                        $value = $value->toString($model->getWithDefault($key, 'storageFormat', 'yyyy-MM-dd HH:mm:ss'));
                    }
                }
            }

            if (isset($row['token']) && $row['token']) {
                $token = $tracker->getToken($row['token']);

                if ($token->exists && $token->isCompleted() && $token->getReceptionCode()->isSuccess()) {
                    $prevAnswers = true;
                }
            }
            if (! ($token && $token->exists)) {
                if (! (isset($row['track_id']) && $row['track_id'])) {
                    
                }
                // create token?
            }

            if ($answers) {
                if ($prevAnswers) {
                    if (\Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_OVERWRITE == $tokenCompletion) {
                        $code = $this->util->getReceptionCode('redo');

                        $oldComment = "";
                        if ($token->getComment()) {
                            $oldComment .= "\n\n";
                            $oldComment .= $this->_('Previous comments:');
                            $oldComment .= "\n";
                            $oldComment .= $token->getComment();
                        }
                        $newComment = sprintf($this->_('Token %s overwritten by import.'), $token->getTokenId());

                        $replacementTokenId = $token->createReplacement($newComment . $oldComment, $userId);

                        $count = $batch->addToCounter('overwritten', 1);
                        $batch->setMessage('overwritten', sprintf(
                                $this->plural('%d token overwrote an existing token.',
                                        '%d tokens overwrote existing tokens.',
                                        $count),
                                $count));

                        $oldToken = $token;
                        $token    = $tracker->getToken($replacementTokenId);

                        // Make sure the Next token is set right
                        $oldToken->setNextToken($token);

                        $oldToken->setReceptionCode(
                                $code,
                                sprintf($this->_('Token %s overwritten by import.'), $token->getTokenId()) . $oldComment,
                                $userId
                        );
                    } else {
                        $oldComment = "";
                        if ($token->getComment()) {
                            $oldComment .= "\n\n";
                            $oldComment .= $this->_('Previous comments:');
                            $oldComment .= "\n";
                            $oldComment .= $token->getComment();
                        }
                        $newComment = sprintf($this->_('More answers for token %s by import.'), $token->getTokenId());

                        $replacementTokenId = $token->createReplacement($newComment . $oldComment, $userId);

                        $count = $batch->addToCounter('addedAnswers', 1);
                        $batch->setMessage('addedAnswers', sprintf(
                                $this->plural('%d token was imported as a new extra token.',
                                        '%d tokens were imported as a new extra token.',
                                        $count),
                                $count));

                        $oldToken = $token;
                        $token    = $tracker->getToken($replacementTokenId);

                        // Make sure the Next token is set right
                        $oldToken->setNextToken($token);
                        $oldToken->setReceptionCode(
                                $oldToken->getReceptionCode(),
                                sprintf($this->_('Additional answers in imported token %s.'), $token->getTokenId()) . $oldComment,
                                $userId
                        );
                    }
                }

                // There are still answers left to save

                // Make sure the token is known
                $token->getUrl($this->locale->getLanguage(), $userId);

                $token->setRawAnswers($answers);

                if (isset($row['completion_date']) && $row['completion_date']) {
                    $token->setCompletionTime($row['completion_date'], $userId);
                } elseif (! $token->isCompleted()) {
                    $token->setCompletionTime(new \MUtil\Date(), $userId);
                }
                $token->getRespondentTrack()->checkTrackTokens($userId, $token);

                $count = $batch->addToCounter('changed', 1);
                $batch->setMessage('changed', sprintf(
                        $this->plural('%d token imported.',
                                '%d tokens imported.',
                                $count),
                        $count));
            }
        }
    }
}
