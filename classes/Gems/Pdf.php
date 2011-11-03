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
 */

/**
 * File description of Pdf
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @since 1.1
 * @version 1.1
 * @package Gems
 * @subpackage Pdf
 */

/**
 * Class description of Pdf
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @package Gems
 * @subpackage PDf
 */
class Gems_Pdf extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    protected $pageFont      = Zend_Pdf_Font::FONT_COURIER;
    protected $pageFontSize  = 12;
    protected $pageX         = 10;
    protected $pageXfromLeft = true;
    protected $pageY         = 20;
    protected $pageYfromTop  = true;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    public function addTokenToDocument(Zend_Pdf $pdf, $tokenId, $surveyId)
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
        $pdf->properties['ModDate'] = 'D:' . str_replace(':', "'", date('YmdHisP')) . "'";
    }

    public function addTokenToPage(Zend_Pdf_Page $page, $tokenId)
    {
        // Set $this->pageFont to false to prevent drawing of tokens on page.
        if ($this->pageFont) {
            $font = Zend_Pdf_Font::fontWithName($this->pageFont);

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

    public function echoPdf(Zend_Pdf $pdf, $filename, $download = false)
    {
        // We do not need to return the layout, just the above table
        Zend_Layout::resetMvcInstance();

        $content = $pdf->render();

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

    public function echoPdfBySurveyId($surveyId)
    {
        $pdf = $this->getSurveyPdf($surveyId);

        $this->echoPdf($pdf, $surveyId . '.pdf');
    }

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
     * @return Zend_Pdf
     */
    protected function getSurveyPdf($surveyId)
    {
        $filename = $this->db->fetchOne('SELECT gsu_survey_pdf FROM gems__surveys WHERE gsu_id_survey = ?', $surveyId);

        if (! $filename) {
            $filename = $surveyId . '.pdf';
            // $this->throwLastError("No PDF Source for survey '$surveyId' does exist!");
        }

        $filepath = $this->getSurveysDir() . '/' . $filename;

        if (! file_exists($filepath)) {
            MUtil_Echo::r($filepath);
            $this->throwLastError("PDF Source File '$filename' not found!");
        }

        return Zend_Pdf::load($filepath);
    }

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
                $this->throwLastError("Could not create '$dir' directory.");
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

    private function throwLastError($msg)
    {
        if ($last = error_get_last()) {
            $msg .= ' The error message is: ' . $last['message'];
        }
        throw new Gems_Exception_Coding($msg);
    }
}
