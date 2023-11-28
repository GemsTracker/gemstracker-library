<?php

namespace Gems\Model\Type;

use Gems\Locale\Locale;
use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Gems\Repository\MailRepository;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class CommTemplateSingleLanguageFlatModel extends GemsJoinModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Locale $locale,
        protected readonly MailRepository $mailRepository,
        protected readonly Translated $translatedUtil,
    ) {
        parent::__construct('gems__comm_templates', $metaModelLoader, $sqlRunner, $translate, 'commTemplate');

        $this->addLeftTable('gems__comm_template_translations', ['gctt_id_template' => 'gct_id_template', 'gctt_lang' => '\'' . $locale->getLanguage() . '\'']);

        $this->metaModel->set('gct_id_template', [
            'apiName' => 'id',
            'elementClass' => 'hidden',
        ]);
        $this->metaModel->set('gct_name', [
            'label' => $translate->_('Name'),
            'size' => 50,
            'apiName' => 'name',
        ]);

        $commTargets = $this->mailRepository->getMailTargets();
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

        $this->metaModel->set('gctt_subject', [
            'label' => $this->translate->_('Subject'),
            'apiName' => 'subject',
            'size' => 50,
        ]);
        $this->metaModel->set('gctt_body', [
            'label' => $this->translate->_('Body'),
            'apiName' => 'body',
        ]);

        $metaModelLoader->setChangeFields($this->metaModel, 'gct');
    }
}