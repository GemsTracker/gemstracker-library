<?php

namespace Gems\Model;

use Gems\Locale\Locale;
use Gems\Model;
use MUtil\Model\TableModel;
use MUtil\Model\Transform\NestedTransformer;
use MUtil\Model\Transform\RequiredRowsTransformer;
use MUtil\Translate\Translator;

class CommTemplateModel extends JoinModel
{
    public function __construct(protected Translator $translator, protected Locale $locale, protected array $config)
    {
        parent::__construct('commTemplate', 'gems__comm_templates', 'gct', true);

        $this->set('gct_id_template', [
            'apiName' => 'id',
        ]);
        $this->set('gct_name', [
            'label' => $translator->_('Name'),
            'apiName' => 'name',
        ]);
        $this->set('gct_target', [
            'label' => $translator->_('Mail Target'),
            'apiName' => 'mailTarget',
        ]);
        $this->set('gct_code', [
            'label' => $translator->_('Code'),
            'apiName' => 'code',
            'description' => $translator->_('Optional code name to link the template to program code.'),
        ]);

        Model::setChangeFieldsByPrefix($this, 'gct');

        $subModel = new TableModel('gems__comm_template_translations');
        $subModel->set('gctt_lang', [
            'label' => $translator->_('Language'),
            'apiName' => 'language',
        ]);
        $subModel->set('gctt_subject', [
            'label' => $translator->_('Subject'),
            'apiName' => 'subject',
        ]);
        $subModel->set('gctt_body', [
            'label' => $translator->_('Body'),
            'apiName' => 'body',
        ]);

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

        $requiredRowsTransformer = new RequiredRowsTransformer();
        $requiredRowsTransformer->setRequiredRows($requiredRows);
        $subModel->addTransformer($requiredRowsTransformer);

        $trans = new NestedTransformer();
        $trans->addModel($subModel, [
            'gct_id_template' => 'gctt_id_template',
        ],
            'translations');

        $this->addTransformer($trans);
        $this->set('translations',
            'model', $subModel,
            'elementClass', 'FormTable',
            'type', \MUtil\Model::TYPE_CHILD_MODEL
        );

        $this->addModel($subModel, [
            'gct_id_template' => 'gctt_id_template',
        ],
        'translations');
    }
}