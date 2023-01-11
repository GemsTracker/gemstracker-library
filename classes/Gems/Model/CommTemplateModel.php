<?php

namespace Gems\Model;

use Gems\Locale\Locale;
use Gems\Model;
use Gems\Model\Transform\HtmlSanitizeTransformer;
use Gems\Repository\MailRepository;
use Gems\Util\Translated;
use MUtil\Model\TableModel;
use MUtil\Translate\Translator;
use Zalt\Model\MetaModelInterface;

class CommTemplateModel extends JoinModel
{
    public function __construct(
        protected Translator $translator,
        protected Locale $locale,
        protected MailRepository $mailRepository,
        protected array $config,
        protected Translated $translatedUtil,
    )
    {
        parent::__construct('commTemplate', 'gems__comm_templates', 'gct', true);

        $this->set('gct_id_template', [
            'apiName' => 'id',
            'elementClass' => 'hidden',
        ]);
        $this->set('gct_name', [
            'label' => $translator->_('Name'),
            'size' => 50,
            'apiName' => 'name',
        ]);

        $commTargets = $this->mailRepository->getMailTargets();
        $this->set('gct_target', [
            'label' => $translator->_('Mail Target'),
            'apiName' => 'mailTarget',
            'multiOptions' => $commTargets,
            'formatFunction' => [$this->translator, 'trans'],
        ]);
        if (array_key_exists('token', $commTargets)) {
            $this->set('gct_target', [
                'default' => 'token',
            ]);
        }


        $this->set('gct_code', [
            'label' => $translator->_('Code'),
            'apiName' => 'code',
            'description' => $translator->_('Optional code name to link the template to program code.'),
            'formatFunction' => [$this->translatedUtil, 'markEmpty'],
        ]);

        Model::setChangeFieldsByPrefix($this, 'gct');

        $subModel = new TableModel('gems__comm_template_translations');
        $subModel->set('gctt_lang', [
            'label' => $translator->_('Language'),
            'apiName' => 'language',
            'multiOptions' => array_combine($this->locale->getAvailableLanguages(), $this->locale->getAvailableLanguages()),
            'elementClass' => 'Html',
        ]);
        $subModel->set('gctt_subject', [
            'label' => $translator->_('Subject'),
            'apiName' => 'subject',
            'formatFunction' => [$this->translatedUtil, 'markEmpty'],
        ]);
        $subModel->set('gctt_body', [
            'label' => $translator->_('Body'),
            'apiName' => 'body',
            'elementClass' => 'Textarea',
            'cols' => 100,
            'rows' => 10
        ]);

        $subModel->addTransformer(new HtmlSanitizeTransformer(['gctt_subject', 'gctt_body']));

        $requiredRows = [
            [
                'gctt_lang' => $this->locale->getDefaultLanguage(),
            ],
        ];
        if (isset($config['email'], $config['email']['multiLanguage']) && $config['email']['multiLanguage'] === true) {
            $languages = $this->locale->getAvailableLanguages();
            $requiredRows = [];
            foreach($languages as $code) {
                $requiredRows[]['gctt_lang'] = $code;
            }
        }

        $oneToMany = new OneToManyTransformer();
        $oneToMany->addModel($subModel, [
            'gct_id_template' => 'gctt_id_template',
        ],
            'translations');

        $this->addTransformer($oneToMany);
        $this->set('translations', [
            'model' => $subModel,
            'elementClass' => 'commTemplateTranslations',
            'type' => MetaModelInterface::TYPE_CHILD_MODEL,
            'label' => $this->translator->_('Translations'),
        ]);

        $requiredRowsTransformer = new Model\Transform\SubmodelRequiredRows('translations');
        $requiredRowsTransformer->setRequiredRows($requiredRows);
        $this->addTransformer($requiredRowsTransformer);
    }
}