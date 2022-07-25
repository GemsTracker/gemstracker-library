<?php
/**
 * Description of PdfTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
namespace Gems\Pdf;

class PdfTest extends \Gems\Test\TestAbstract {

    public function setUp() {
        parent::setUp();

        $settings         = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/project.example.ini', APPLICATION_ENV);
        $settings         = $settings->toArray();
        $settings['salt'] = 'vadf2646fakjndkjn24656452vqk';
        $project          = new Gems\Project\ProjectSettings($settings);

        \Zend_Registry::set('project', $project);
    }

    public function testRender() {
        $pdf      = \Zend_Pdf::load(__DIR__ . '/MSWord.pdf');
        $expected = \Zend_Pdf::load(__DIR__ . '/MSWordExpected.pdf');
        $gemsPdf  = $this->loader->getPdf();

        // Start code to expose protected method
        $addTokenToDocument = function(\Zend_Pdf $pdf, $tokenId, $surveyId) {
            return $this->addTokenToDocument($pdf, $tokenId, $surveyId);
        };

        $testObject = $addTokenToDocument->bindTo($gemsPdf, $gemsPdf);
        // End code to expose protected method
        $testObject($pdf, "abcd-efgh", 1);

        // fix the date to prevent differences
        $pdf->properties['ModDate'] = 'D:' . str_replace(':', "'", date('YmdHisP', 1458048768)) . "'";

        // Uncomment to update the test pdf
        /*
          $stream = fopen(__DIR__ . '/MSWordExpected.pdf', 'w');
          $pdf->render(false, $stream);
          fclose($stream);
         */
         
        // This will trigger the warning in #812: Warning when printing a survey pdf that was created using Word
        // This warning will cause the test to fail
        $pdf->render();

        $this->assertEquals($expected->getMetadata(), $pdf->getMetadata());
        $this->assertEquals($expected->properties, $pdf->properties);
    }

}
