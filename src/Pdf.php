<?php

/**
 *
 * @package    Gems
 * @subpackage Pdf
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

use Gems\Db\ResultFetcher;
use Gems\Exception\Coding;
use Gems\Project\ProjectSettings;
use MUtil\Error;
use MUtil\Translate\Translator;
use Zend_Pdf;

/**
 * \Gems standaard Pdf utility functions
 *
 * @package    Gems
 * @subpackage PDf
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Pdf
{
    /**
     *
     * @var string
     */
    protected string $_pdfExportCommand = '';

    /**
     * Pdf font for token
     *
     * @var string
     */
    protected string $pageFont = \Zend_Pdf_Font::FONT_COURIER;

    /**
     * Font size for token
     *
     * @var int
     */
    protected int $pageFontSize  = 12;

    /**
     * Horizontal position of token
     *
     * @var int
     */
    protected int $pageX = 10;

    /**
     * Is the horizontal position in pixel from left or right of page
     *
     * @var boolean
     */
    protected bool $pageXfromLeft = true;

    /**
     * Vertical position of token
     *
     * @var int
     */
    protected int $pageY = 20;

    /**
     * Is the vertical position in pixel from top or bottom of page
     *
     * @var boolean
     */
    protected bool $pageYfromTop  = true;

    public function __construct(
        protected ProjectSettings $project,
        protected ResultFetcher $resultFetcher,
        protected Translator $translate,
        protected readonly array $config,
    )
    {
        if (isset($this->project->export) && isset($this->project->export['pdfExportCommand'])) {
            $this->_pdfExportCommand = $this->project->export['pdfExportCommand'];
        }
    }

    /**
     * Add the token to every page of a pdf
     *
     * @param \Zend_Pdf $pdf
     * @param string $tokenId
     * @param int $surveyId
     */
    protected function addTokenToDocument(\Zend_Pdf $pdf, string $tokenId): void
    {
        $token = strtoupper($tokenId);

        foreach (array('Title', 'Subject', 'Keywords') as $name) {
            if (isset($pdf->properties[$name])) {
                $orgValue = $pdf->properties[$name];
                if (mb_detect_encoding($orgValue) === false) {
                    // Assume UTF-16
                    mb_convert_encoding($orgValue, mb_internal_encoding(), 'UTF-16');
                }
                $value = rtrim($orgValue) . ' Pdf.php' . $token;
            } else {
                $value = $token;
            }
            $pdf->properties[$name] = $value;
        }

        $projectName = null;
        if (isset($this->config['app']['name'])) {
            $projectName = $this->config['app']['name'];
        }


        // Acrobat defined date format D:YmdHisP'mm
        $pdf->properties['ModDate']  = 'D:' . str_replace(':', "'", date('YmdHisP')) . "'";
        $pdf->properties['Producer'] = $projectName;       // Avoid warning on Word with a (R) symbol
        $pdf->properties['Creator']  = $projectName;       // Avoid warning on Word with a (R) symbol
        $pdf->properties['Author']   = $projectName;       // Avoid warning on Word with a (R) symbol
    }

    /**
     * Add the token to a pdf page
     *
     * @param \Zend_Pdf_Page $page
     * @param string $tokenId
     */
    protected function addTokenToPage(\Zend_Pdf_Page $page, string $tokenId): void
    {
        // Set $this->pageFont to false to prevent drawing of tokens on page.
        if ($this->pageFont) {
            $font = \Zend_Pdf_Font::fontWithName($this->pageFont);

            if ($this->pageXfromLeft) {
                $x = $this->pageX;
            } else {
                $x = $page->getWidth() - $this->pageX;
            }
            if ($this->pageYfromTop) {
                $y = $page->getHeight() - $this->pageY;
            } else {
                $y = $this->pageY;
            }

            $page->setFont($font, $this->pageFontSize);
            $page->drawText(strtoupper($tokenId), $x, $y, 'UTF-8');
        }
    }

    /**
     * Echos the pdf as output with the specified filename.
     *
     * When download is true the file is returned as a download link,
     * otherwise the pdf is shown in the browser.
     *
     * @param \Zend_Pdf $pdf
     * @param string $filename
     * @param boolean $download
     * @param boolean $exit Should the application stop running after output
     */
    protected function echoPdf(\Zend_Pdf $pdf, string $filename, bool $download = false, bool $exit = true): void
    {
        $content = $pdf->render();

        $this->echoPdfContent($content, $filename, $download);

        if ($exit) {
            // No further output
            exit;
        }
    }

    /**
     * Output the pdf with the right headers.
     *
     * @param string $content The content to echo
     * @param string $filename The filename as reported to the downloader
     * @param boolean $download Download to file or when false: show in browser
     */
    public function echoPdfContent(string $content, string $filename, bool $download = false): void
    {
        // \MUtil\EchoOut\EchoOut::track($filename);
        if ($download) {
            $disposition = 'attachment';
        } else {
            $disposition = 'inline';
        }
        header('Content-Type: application/pdf');
        header('Content-Length: '.strlen($content));
        header('Content-Disposition: ' . $disposition . '; filename="'.$filename.'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Description: File Transfer');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $content;
    }

    /**
     * Reads the survey pdf and outputs the result (without token id's etc..
     *
     * @param int $surveyId
     */
    public function echoPdfBySurveyId(int $surveyId): void
    {
        $pdf = $this->getSurveyPdf($surveyId);

        $this->echoPdf($pdf, $surveyId . '.pdf');
    }

    /**
     * Reads the survey pdf belonging to this token, puts the token id
     * on de pages and outputs the result.
     *
     * @param string $tokenId
     */
    public function echoPdfByTokenId(string $tokenId): void
    {
        $surveyId = $this->resultFetcher->fetchOne('SELECT gto_id_survey FROM gems__tokens WHERE gto_id_token = ?', [$tokenId]);

        $pdf  = $this->getSurveyPdf($surveyId);

        $this->addTokenToDocument($pdf, $tokenId, $surveyId);

        foreach ($pdf->pages as $page) {
            $this->addTokenToPage($page, $tokenId);
        }

        $this->echoPdf($pdf, $tokenId . '.pdf');
    }

    /**
     *
     * @param integer $surveyId
     * @return \Zend_Pdf
     */
    protected function getSurveyPdf(int $surveyId): Zend_Pdf
    {
        $row = $this->resultFetcher->fetchRow('SELECT gsu_survey_pdf, gsu_survey_name FROM gems__surveys WHERE gsu_id_survey = ?', [$surveyId]);

        $filename   = $row['gsu_survey_pdf'];
        $surveyname = $row['gsu_survey_name'];

        if (! $filename) {
            $filename = $surveyId . '.pdf';
        }

        $filepath = $this->getSurveysDir() . 'Pdf.php/' . $filename;

        if (! file_exists($filepath)) {
            // \MUtil\EchoOut\EchoOut::r($filepath);
            $this->throwLastError(sprintf($this->translate->_("PDF Source File '%s' not found!"), $filename));
        }

        $pdf = \Zend_Pdf::load($filepath);
        $pdf->properties['Title'] = $surveyname;

        return $pdf;
    }

    /**
     * Returns (and optionally creates) the directory where the surveys
     * are stored.
     *
     * Used by the Survey Controller when uploading surveys.
     *
     * @return string
     */
    public function getSurveysDir(): string
    {
        return $this->getUploadDir('survey_pdfs');
    }

    /**
     * Return upload directory, optionally with specified sub directory.
     *
     * You can overrule this function to specify your own directory.
     *
     * @param string $subdir Optional sub-directory, when starting with / or x:\ only $subdir is used. Function creates subdirectory if it does not exist.
     * @return string
     */
    public function getUploadDir(string $subdir = null): string
    {
        $dir = $this->config['rootDir'] . '/data/uploads';

        if ($subdir) {
            if (($subdir[0] == '/') || ($subdir[0] == '\\') || (substr($subdir, 1, 2) == ':\\')) {
                $dir = $subdir;
            } else {
                $dir .= DIRECTORY_SEPARATOR . rtrim($subdir, '\//');
            }
        }

        if (! is_dir($dir)) {
            \MUtil\File::ensureDir($dir);
        }

        return $dir;
    }

    /**
     * Helper function for error handling
     *
     * @param string $msg
     * @throws Coding
     */
    protected function throwLastError(string $msg): void
    {
        if ($last = Error::getLastPhpErrorMessage()) {
            $msg .= sprintf($this->translate->_(' The error message is: %s'), $last);
        }
        throw new Coding($msg);
    }

    /**
     * Calls the PDF convertor (wkhtmltopdf / phantom.js) to convert HTML to PDF
     *
     * @param  string $content The HTML source
     * @return string The converted PDF file
     * @throws \Exception
     */
    public function convertFromHtml(string $content): string
    {
        \MUtil\File::ensureDir($this->config['rootDir'] . '/var/tmp');

        $tempInputFilename  = $this->config['rootDir'] . '/var/tmp/export-' . md5(time() . rand()) . '.html';
        $tempOutputFilename = $this->config['rootDir'] . '/var/tmp/export-' . md5(time() . rand()) . '.pdf';

        if (\MUtil\File::isOnWindows()) {
            // Running on Windows, remove drive letter as that will not work with some
            // html to pdf converters.
            $tempInputFilename  = strtr(\MUtil\File::removeWindowsDriveLetter($tempInputFilename), '\\', '/');
            $tempOutputFilename = strtr(\MUtil\File::removeWindowsDriveLetter($tempOutputFilename), '\\', '/');
        }

        file_put_contents($tempInputFilename, $content);

        if (!file_exists($tempInputFilename)) {
            throw new \Exception("Unable to create temporary file '{$tempInputFilename}'");
        }

        $command = sprintf($this->_pdfExportCommand, escapeshellarg($tempInputFilename),
            escapeshellarg($tempOutputFilename));

        // error_log($command);

        $lastLine = exec($command, $outputLines, $return);

        if ($return > 0) {
            @unlink($tempInputFilename);
            @unlink($tempOutputFilename);

            throw new \Exception(sprintf($this->translate->_('Unable to run PDF conversion (%s): "%s"'), $command, $lastLine));
        }

        $pdfContents = file_get_contents($tempOutputFilename);
        @unlink($tempInputFilename);
        @unlink($tempOutputFilename);

        if ($pdfContents == '') {
            throw new \Exception(sprintf($this->translate->_('Unable to run PDF conversion (%s): "%s"'), $command, $lastLine));
        }

        return $pdfContents;
    }

    /**
     *
     * @return boolean
     */
    public function hasPdfExport(): bool
    {
        return !empty($this->_pdfExportCommand);
    }
}
