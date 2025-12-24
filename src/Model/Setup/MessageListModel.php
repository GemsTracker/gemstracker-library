<?php

declare(strict_types=1);

namespace Gems\Model\Setup;

use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Gems\Model\Setup\Transform\MessageBodyInfoTransformer;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Html\HtmlElement;
use Zalt\Model\Sql\SqlRunnerInterface;

class MessageListModel extends GemsJoinModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
    ) {
        parent::__construct('messenger_messages', $metaModelLoader, $sqlRunner, $translate, 'messengerList', false);

        $this->metaModel->set('queue_name', [
            'label' => $this->_('Queue'),
            'order' => 10,
        ]);

        $this->metaModel->set('messageShortClass', [
            'label' => $this->_('Message type'),
            SqlRunnerInterface::NO_SQL => true,
            'no_text_search' => true,
            'order' => 20,
        ]);

        $this->metaModel->set('messageInfo', [
            'label' => $this->_('Message info'),
            SqlRunnerInterface::NO_SQL => true,
            'no_text_search' => true,
            'order' => 30,
        ]);

        $this->metaModel->set('created_at', [
            'label' => $this->_('Created'),
            'order' => 40,
        ]);

        $this->metaModel->set('available_at', [
            'label' => $this->_('Available'),
            'order' => 50,
        ]);

        $this->metaModel->set('delivered_at', [
            'label' => $this->_('Delivered'),
            'order' => 60,
        ]);

        $this->metaModel->addTransformer(new MessageBodyInfoTransformer());
    }

    public function applyDetailSettings(): void
    {
        $this->metaModel->set('message', [
            'label' => $this->_('Message'),
            SqlRunnerInterface::NO_SQL => true,
            'no_text_search' => true,
            'order' => 70,
            'formatFunction' => [$this, 'prePrint'],
        ]);

        $this->metaModel->set('stamps', [
            'label' => $this->_('Stamps'),
            SqlRunnerInterface::NO_SQL => true,
            'no_text_search' => true,
            'order' => 80,
            'formatFunction' => [$this, 'prePrintStamps'],
        ]);
    }

    public function prePrintStamps(array $stampList): HtmlElement
    {
        $container = Html::div();
        foreach($stampList as $stampType => $stamps) {
            foreach ($stamps as $stamp) {
                $container->pre()->append(print_r($stamp, true));
                $container->hr();
            }
        }

        return $container;
    }

    public function prePrint(object|null $value): HtmlElement
    {
        return $this->pre(print_r($value, true));
    }

    public function pre(int|string|null $value): HtmlElement
    {
        return Html::create('pre')->append($value);
    }

}