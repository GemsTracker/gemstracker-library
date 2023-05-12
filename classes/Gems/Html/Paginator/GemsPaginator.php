<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Html\Paginator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Html\Paginator;

use Gems\Html;
use Zalt\Html\HtmlElement;
use Zalt\Html\HtmlInterface;
use Zalt\Html\Paginator\TranslatablePaginatorTrait;
use Zalt\Html\Sequence;


/**
 * @package    Gems
 * @subpackage Html\Paginator
 * @since      Class available since version 1.0
 */
class GemsPaginator extends \Zalt\Html\Paginator\LinkPaginator
{
    use TranslatablePaginatorTrait;

    public string $itemsClass = 'pagination-index rightFloat pull-right';

    protected string $itemsLinkClass = 'browselink btn btn-sm';

    protected string $itemsDisabledClass = 'browselink btn btn-sm disabled';

    protected string $pageDisabledClass = 'page-link';

    protected string $pageLinkClass = 'page-link';

    public string $pagesClass = 'pagination pagination-sm pull-left';

    public string $pageItemClass = 'page-item';

    public function getFirstPageLabel(): ?string
    {
        return $this->_('<< First');
    }

    public function getHtmlPagelinks(): HtmlInterface
    {
        $items = $this->getItemsList();
        $pages = $this->getPages();

        return new Sequence($items, $pages);
    }

    protected function getItems(): array
    {
        $start = (($this->pageNumber - 1) * $this->pageItems) + 1;
        $end   = min($this->pageNumber * $this->pageItems, $this->itemCount);

        return [
            sprintf($this->_('%d to '), $start),
            $this->getItemLink($this->getLessItems(), '-'),
            $end,
            $this->getItemLink($this->getMoreItems(), '+'),
            sprintf($this->_('of %d'), $this->itemCount),
        ];
    }

    protected function getItemsHolder(): HtmlInterface
    {
        return Html::create('div', ['class' => $this->itemsClass]);
    }

    public function getLastPageLabel(): ?string
    {
        return $this->_('Last >>');
    }

    /**
     * @return string|null Null for not output, string for output
     */
    public function getNextPageLabel(): ?string
    {
        return $this->_('Next >');
    }

    protected function getPageLink(int $pageNumber, ?string $label, bool $isSpecialLink): ?HtmlElement
    {
        $output = parent::getPageLink($pageNumber, $label, $isSpecialLink);

        if (null === $output) {
            return null;
        }

        if ($pageNumber === $this->pageNumber) {
            $class = $this->pageItemClass;
            if ($isSpecialLink) {
                $class .= ' disabled';
            } else {
                $class .= ' active';
            }
            return Html::create('li', $output, ['class' => $class]);
        }

        return Html::create('li', $output);
    }

    protected function getPagesHolder(): HtmlInterface
    {
        return Html::create('ul', ['class' => $this->pagesClass]);
    }

    /**
     * @return string|null Null for not output, string for output
     */
    public function getPreviousPageLabel(): ?string
    {
        return $this->_('< Previous');
    }
}