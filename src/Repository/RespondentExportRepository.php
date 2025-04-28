<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Repository
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Repository;

use Gems\Model\MetaModelLoader;
use Mezzio\Session\SessionInterface;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html as HtmlWord;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HtmlInterface;
use Zalt\Model\Ra\SessionModel;

/**
 * @package    Gems
 * @subpackage Repository
 * @since      Class available since version 1.0
 */
class RespondentExportRepository
{
    use TranslateableTrait;

    public function __construct(
        TranslatorInterface $translator,
        protected readonly MetaModelLoader $metaModelLoader,
    )
    {
        $this->translate = $translator;
    }

    /**
     * Clean DOM object for Word export
     *
     * @param \DOMDocument $dom
     */
    public function cleanDom(\DOMDocument $dom)
    {
        // add border attributes to tables
        foreach ($dom->getElementsByTagName('table') as $tablenode) {
            $tablenode->setAttribute('style','border: 2px #000000 solid;');
        }

        // add font weight attributes to th elements
        foreach ($dom->getElementsByTagName('th') as $thnode) {
            $thnode->setAttribute('style','font-weight: bold;');
        }

        // replace h1-h6 elements for PHP Word processing
        for ($i = 1; $i <= 6; $i++) {
            $fontsize = 26 - ($i * 2);
            do {
                // The list is dynamic so search again until no longer found
                $headers = $dom->getElementsByTagName('h' . $i);
                $headerNode = $headers->item(0);
                if ($headerNode) {
                    $newHeaderNode = $dom->createElement("p", $headerNode->nodeValue);
                    $newHeaderNode->setAttribute('style', 'font-weight: bolder; font-size: ' . $fontsize . 'px;');
                    $headerNode->parentNode->replaceChild($newHeaderNode, $headerNode);
                }
            } while  ($headers->length > 0);
        }

        do {
            // The list is dynamic so search again until no longer found
            $canvas = $dom->getElementsByTagName('canvas' . $i);
            $canvasNode = $canvas->item(0);
            if ($canvasNode) {
                $canvasNode->parentNode->removeChild($canvasNode);

            }
        } while  ($canvas->length > 0);

        return $dom;
    }


    public function getDefaultFormat(array $formats = []): string
    {
        if (! $formats) {
            $formats = $this->getFormats();
        }

        return array_key_last($formats);
        // return array_key_first($formats);
    }

    public function getFileResponse(HtmlInterface $html, string $filebasename, array $formData): ?ResponseInterface
    {
        $content = $html->render();
        if ('word' == ($formData['format'] ?? '')) {
            return $this->getWordResponse($content, $filebasename);
        }
        if ('pdf' == ($formData['format'] ?? '')) {
            return $this->getPdfResponse($content, $filebasename);
        }

        return null;
    }

    public function getFormats(): array
    {
        $output['html'] = $this->_('HTML');

        if (class_exists(PhpWord::class)) {
            if (class_exists(\Dompdf\Dompdf::class)) {
                $output['pdf'] = $this->_('PDF');
            }
            $output['word'] = $this->_('Word');
        }

        return $output;
    }

    public function getModel(SessionInterface $session, bool $addGroup = true): SessionModel
    {
        /**
         * @var SessionModel $model
         */
        $model = $this->metaModelLoader->createModel(SessionModel::class, 'respondentExport', $session);

        $metaModel = $model->getMetaModel();

        if ($addGroup) {
            $metaModel->set('group', [
                'label' => $this->_('Group surveys'),
                'default' => true,
                'elementClass' => 'Checkbox',
            ]);
        }

        $formats = $this->getFormats();
        $metaModel->set('format', [
            'label' => $this->_('Output format'),
            'default' => $this->getDefaultFormat($formats),
            'elementClass' => 'Select',
            'multiOptions' => $formats,
            ]);

        return $model;
    }

    public function getPdfResponse(string $content, $filebasename): ?ResponseInterface
    {
        Settings::setPdfRendererPath('vendor/dompdf/dompdf');
        Settings::setPdfRendererName('DomPDF');

        $word   = $this->getWordDocument($content);
        $writer = IOFactory::createWriter($word, 'PDF');

        header("Content-Description: PDF download");
        header("Content-Type: application/pdf");
        header('Content-Disposition: attachment; filename="'.$filebasename.'.pdf"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        $writer->save("php://output");
        die();
    }

    public function getWordDocument(string $content): PhpWord
    {
        $content = $this->prepareWordExport($content);

        $word    = new PhpWord();
        $section = $word->addSection();

        Settings::setOutputEscapingEnabled(true);
        HtmlWord::addHtml($section, $content, false, false);

        return $word;
    }

    public function getWordResponse(string $content, $filebasename): ?ResponseInterface
    {
        $word   = $this->getWordDocument($content);
        $writer = IOFactory::createWriter($word, 'Word2007');

        header("Content-Description: Word download");
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename="' . $filebasename . '.docx"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        $writer->save("php://output");
        die();

        // return null;
    }

    public function hasFormDisplay(array $formData): bool
    {
        return 'html' !== ($formData['format'] ?? '');
    }

    /**
     * Prepares string for Word export
     *
     * @param string $string
     */
    public function prepareWordExport($string)
    {
        // convert encoding
        $string = mb_convert_encoding($string, 'html-entities', 'utf-8');

        // clear scripting
        $string = preg_replace( '@<(script|style|head|header|footer)[^>]*?>.*?</\\1>@si', '', $string );
        $string = preg_replace('/&(?!(#[0-9]{2,4}|[A-z]{2,6})+;)/', '&amp;', $string);

        // clean string using DOM object
        $dom = new \DOMDocument();
        $dom->loadHTML($string);
        // file_put_contents('D:\temp\beforeDom.html', $string);

        $dom = $this->cleanDom($dom);

        $string = $dom->saveHTML();

        // correct breaks for processing
        $string = str_ireplace('<br>', '<br />', $string);

        // clear scripting and unnecessary tags
        $string = preg_replace( '@<(script|style|head|header|footer)[^>]*?>.*?</\\1>@si', '', $string );
        $string = strip_tags( $string, '<p><h1><h2><h3><h4><h5><h6><#text><strong><b><em><i><u><sup><sub><span><font><table><tr><td><th><ul><ol><li><img><br><a>' );

        // Cleanup nested empty tags used with font awesome, as well as links unused because of the cleanup
        $string = preg_replace("@<i[^>]*></i>@si", '', $string);
        $string = preg_replace("@<span[^>]*></span>@si", '', $string);
        $string = preg_replace("@<a[^>]*></a>@si", '', $string);

        return trim($string);
    }
}