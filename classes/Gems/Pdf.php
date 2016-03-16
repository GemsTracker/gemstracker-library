<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Pdf
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Gems standaard Pdf utility functions
 *
 * @package    Gems
 * @subpackage PDf
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Pdf extends \Gems_Registry_TargetAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var string
     */
    protected $_pdfExportCommand = "";

    /**
     * Pdf font for token
     *
     * @var string
     */
    protected $pageFont = \Zend_Pdf_Font::FONT_COURIER;

    /**
     * Font size for token
     *
     * @var int
     */
    protected $pageFontSize  = 12;

    /**
     * Horizontal position of token
     *
     * @var int
     */
    protected $pageX         = 10;

    /**
     * Is the horizontal position in pixel from left or right of page
     *
     * @var boolean
     */
    protected $pageXfromLeft = true;

    /**
     * Vertical position of token
     *
     * @var int
     */
    protected $pageY         = 20;

    /**
     * Is the vertical position in pixel from top or bottom of page
     *
     * @var boolean
     */
    protected $pageYfromTop  = true;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        // Load the pdf class from the project settings if available
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
    protected function addTokenToDocument(\Zend_Pdf $pdf, $tokenId, $surveyId)
    {
        $token = strtoupper($tokenId);

        foreach (array('Title', 'Subject', 'Keywords') as $name) {
            if (isset($pdf->properties[$name])) {
                $value = rtrim($pdf->properties[$name]) . ' ' . $token;
            } else {
                $value = $token;
            }
            $pdf->properties[$name] = $value;
        }
        // Acrobat defined date format D:YYYYMMDDHHmmSSOHH'mm
        $pdf->properties['ModDate']  = 'D:' . str_replace(':', "'", date('YmdHisP')) . "'";
        $pdf->properties['Producer'] = $this->project->getName();       // Avoid warning on Word with a (R) symbol
        $pdf->properties['Creator']  = $this->project->getName();       // Avoid warning on Word with a (R) symbol
    }

    /**
     * Add the token to a pdf page
     *
     * @param \Zend_Pdf_Page $page
     * @param string $tokenId
     */
    protected function addTokenToPage(\Zend_Pdf_Page $page, $tokenId)
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
    protected function echoPdf(\Zend_Pdf $pdf, $filename, $download = false, $exit = true)
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
    public function echoPdfContent($content, $filename, $download = false)
    {
        // \MUtil_Echo::track($filename);
        if ($download) {
            // Download & save
			header('Content-Type: application/x-download');
        } else {
            //We send to a browser
            header('Content-Type: application/pdf');
        }
        header('Content-Length: '.strlen($content));
        header('Content-Disposition: inline; filename="'.$filename.'"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $content;
    }

    /**
     * Reads the survey pdf and outputs the result (without token id's etc..
     *
     * @param string $tokenId
     */
    public function echoPdfBySurveyId($surveyId)
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
    public function echoPdfByTokenId($tokenId)
    {
        $surveyId = $this->db->fetchOne('SELECT gto_id_survey FROM gems__tokens WHERE gto_id_token = ?', $tokenId);

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
    protected function getSurveyPdf($surveyId)
    {
        $filename = $this->db->fetchOne('SELECT gsu_survey_pdf FROM gems__surveys WHERE gsu_id_survey = ?', $surveyId);

        if (! $filename) {
            $filename = $surveyId . '.pdf';
        }

        $filepath = $this->getSurveysDir() . '/' . $filename;

        if (! file_exists($filepath)) {
            // \MUtil_Echo::r($filepath);
            $this->throwLastError(sprintf($this->translate->_("PDF Source File '%s' not found!"), $filename));
        }

        return \Zend_Pdf::load($filepath);
    }

    /**
     * Returns (and optionally creates) the directory where the surveys
     * are stored.
     *
     * Used by the Survey Controller when uploading surveys.
     *
     * @return string
     */
    public function getSurveysDir()
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
    public function getUploadDir($subdir = null)
    {
        $dir = GEMS_ROOT_DIR . '/var/uploads';

        if ($subdir) {
            if (($subdir[0] == '/') || ($subdir[0] == '\\') || (substr($subdir, 1, 2) == ':\\')) {
                $dir = rtrim($subdir, '\//');
            } else {
                $dir .= DIRECTORY_SEPARATOR . rtrim($subdir, '\//');
            }
        }

        if (! is_dir($dir)) {
            $oldmask = umask(0777);
            if (! @mkdir($dir, 0777, true)) {
                $this->throwLastError(sprintf($this->translate->_("Could not create '%s' directory."), $dir));
            }
            umask($oldmask);
        }

        return $dir;
    }

    /**
     * Rename existing file.
     *
     * @param string $source
     * @param string $destination
     * @return string
     * /
    public function rename($source, $destination)
    {
        if (file_exists($source)) {
            $dir = dirname($destination);
            if (! is_dir($dir)) {
                $oldmask = umask(0777);
                if (! @mkdir($dir, 0777, true)) {
                    $this->throwLastError("Could not create '$dir' directory.");
                }
                umask($oldmask);
            }
            if (file_exists($destination)) {
                if (! unlink($destination)) {
                    $this->throwLastError("Could not remove existing '$destination' file.");
                }
            }
            if (! rename($source, $destination)) {
                $this->throwLastError("Could not rename '$source' to '$destination'.");
            }

        } else {
            $this->throwLastError("Source file '$source' does not exist.");
        }
    } */

    protected function throwLastError($msg)
    {
        if ($last = \MUtil_Error::getLastPhpErrorMessage()) {
            $msg .= sprintf($this->translate->_(' The error message is: %s'), $last);
        }
        throw new \Gems_Exception_Coding($msg);
    }

    /**
     * Calls the PDF convertor (wkhtmltopdf / phantom.js) to convert HTML to PDF
     *
     * @param  string $content The HTML source
     * @return string The converted PDF file
     * @throws \Exception
     */
    public function convertFromHtml($content)
    {
        $tempInputFilename  = GEMS_ROOT_DIR . '/var/tmp/export-' . md5(time() . rand()) . '.html';
        $tempOutputFilename = GEMS_ROOT_DIR . '/var/tmp/export-' . md5(time() . rand()) . '.pdf';

        file_put_contents($tempInputFilename, $content);

        if (!file_exists($tempInputFilename)) {
            throw new \Exception("Unable to create temporary file '{$tempInputFilename}'");
        }

        $command = sprintf($this->_pdfExportCommand, escapeshellarg($tempInputFilename),
            escapeshellarg($tempOutputFilename));

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
    public function hasPdfExport()
    {
        return !empty($this->_pdfExportCommand);
    }
}
