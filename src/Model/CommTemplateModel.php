<?php

namespace Gems\Model;

use Gems\Html;
use Gems\Locale\Locale;
use Gems\Model\Transform\HtmlSanitizeTransformer;
use Gems\Repository\CommFieldRepository;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Raw;
use Zalt\Html\Sequence;
use Zalt\Model\Bridge\DisplayBridge;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Transform\SubmodelRequiredRowsTransformer;

class CommTemplateModel extends GemsJoinModel
{
    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected CommFieldRepository $commFieldRepository,
        protected readonly array $config,
        protected readonly Translated $translatedUtil,
        protected readonly Locale $locale,
    ) {
        parent::__construct('gems__comm_templates', $metaModelLoader, $sqlRunner, $translate, 'commTemplate');


        $this->metaModel->set('gct_id_template', [
            'apiName' => 'id',
            'elementClass' => 'hidden',
        ]);
        $this->metaModel->set('gct_name', [
            'label' => $translate->_('Name'),
            'size' => 50,
            'apiName' => 'name',
        ]);

        $commTargets = $this->commFieldRepository->getCommFieldTypes();
        $this->metaModel->set('gct_target', [
            'label' => $translate->_('Mail Target'),
            'apiName' => 'mailTarget',
            'multiOptions' => $commTargets,
            'formatFunction' => [$this->translate, 'trans'],
        ]);
        if (array_key_exists('token', $commTargets)) {
            $this->metaModel->set('gct_target', [
                'default' => 'token',
            ]);
        }

        $this->metaModel->set('gct_code', [
            'label' => $translate->_('Code'),
            'apiName' => 'code',
            'description' => $translate->_('Optional code name to link the template to program code.'),
            'formatFunction' => [$this->translatedUtil, 'markEmpty'],
        ]);

        $metaModelLoader->setChangeFields($this->metaModel, 'gct');
    }

    public function applyBrowseSettings()
    {

    }

    public function applyDetailSettings()
    {
        $subModel = $this->getSubModel();
        $subModelMetaModel = $subModel->getMetaModel();

        $subModelMetaModel->addTransformer(new HtmlSanitizeTransformer(['gctt_subject', 'gctt_body']));

        $oneToMany = new OneToManyTransformer();
        $oneToMany->addModel($subModel, [
                'gct_id_template' => 'gctt_id_template',
            ],
            'translations');

        $this->metaModel->addTransformer($oneToMany);
        $this->metaModel->set('translations', [
            'label' => $this->translate->_('Translations'),
            'formatFunction' => [$this, 'displayTranslations'],
            'elementClass' => 'commTemplateTranslations',
            'model' => $subModel,
            'type' => MetaModelInterface::TYPE_CHILD_MODEL,
        ]);

        $requiredRows = [
            [
                'gctt_lang' => $this->locale->getDefaultLanguage(),
            ],
        ];

        $languages = $this->getEmailLanguages();
        $requiredRows = [];
        foreach ($languages as $code) {
            $requiredRows[]['gctt_lang'] = $code;
        }

        $requiredRowsTransformer = new SubmodelRequiredRowsTransformer('translations');
        $requiredRowsTransformer->setRequiredRows($requiredRows);
        $this->metaModel->addTransformer($requiredRowsTransformer);
    }

    protected function getEmailLanguages(): array
    {
        if (isset($this->config['email'], $this->config['email']['multiLanguage']) && $this->config['email']['multiLanguage'] === true) {
            return $this->locale->getAvailableLanguages();
        }

        return [$this->locale->getDefaultLanguage()];
    }

    public function displayTranslations($translations)
    {
        $subModel = $this->getSubModel();
        $subMeta  = $subModel->getMetaModel();
        $bridge   = $this->metaModelLoader->createBridge(DisplayBridge::class, $subModel);

        $html = Html::div(['class' => 'comm-translations']);
        foreach ($translations as $translation) {
            // @phpstan-ignore method.notFound
            $html->hr();
            $div = $html->div(['class' => 'comm-translation']);
            foreach ($translation as $field => $value) {
                $label = $subMeta->get($field, 'label');
                if ($label) {
                    $div->strong($label);
                    $div->p($bridge->format($field, $value));
                }
            }
        }

        return $html;
    }


    protected function getSubModel(): DataReaderInterface
    {
        static $subModel = null;

        if ($subModel) {
            return $subModel;
        }

        $languages = array_combine($this->getEmailLanguages(), $this->getEmailLanguages());

        $subModel = $this->metaModelLoader->createTableModel('gems__comm_template_translations');
        $subMetaModel = $subModel->getMetaModel();
        $subMetaModel->set('gctt_lang', [
            'label' => $this->translate->_('Language'),
            'apiName' => 'language',
            'multiOptions' => $languages,
            'elementClass' => 'Html',
        ]);
        $subMetaModel->set('gctt_subject', [
            'label' => $this->translate->_('Subject'),
            'apiName' => 'subject',
            'size' => 50,
            'formatFunction' => [$this->translatedUtil, 'markEmpty'],
        ]);
        $subMetaModel->set('gctt_body', [
            'label' => $this->translate->_('Body'),
            'apiName' => 'body',
            'elementClass' => 'Textarea',
            'formatFunction' => [Html::class, 'raw'],
            'cols' => 100,
            'rows' => 10
        ]);

        return $subModel;
    }
}