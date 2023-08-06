<?php

declare(strict_types=1);

/**
 * @package    GemsTest
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace GemsTest\Model\Type;

use Gems\Model\Type\TokenValidFromType;
use GemsTest\Model\GemsModelTestTrait;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Ra\PhpArrayModel;

/**
 * @package    GemsTest
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class TokenModelTypeTest extends \PHPUnit\Framework\TestCase
{
    use GemsModelTestTrait;

    protected TokenValidFromType $tokenType;

    public function getModelLoaded($rows): PhpArrayModel
    {
        $loader = $this->getModelLoader();
        $this->tokenType = $loader->createType(TokenValidFromType::class);

        if ($rows instanceof \ArrayObject) {
            $data = $rows;
        } else {
            $data = new \ArrayObject($rows);
        }
        $model = $loader->createModel(PhpArrayModel::class, 'test', $data);

        $metaModel = $model->getMetaModel();
        $metaModel->set('gto_id_token', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_STRING]);
        $metaModel->set('gto_valid_from', [MetaModelInterface::TYPE_ID => $this->tokenType]);
        $metaModel->set('gto_valid_until', [MetaModelInterface::TYPE_ID => $this->tokenType]);
        $metaModel->set('gro_valid_after_unit', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_STRING]);
        $metaModel->set('gro_valid_for_unit', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_STRING]);

        return $model;
    }

    public static function provideValidFrom(): array
    {
        return [
            'fullDate'   => ['2023-03-01 12:13:14', 'H', '01-03-2023 12:13', '2023-03-01 12:13:00'],
            'dayOnly'    => ['2023-03-01 12:13:14', 'D', '01-03-2023 12:13', '2023-03-01 12:13:00'],
            'atMidNight' => ['2023-03-01 00:00:00', 'H', '01-03-2023', '2023-03-01 00:00:00'],
        ];
    }

    /**
     * @dataProvider provideValidFrom
     *
     * @param string $from
     * @param string $period
     * @param string $storage
     * @return void
     */
    public function testValidFrom(string $from, string $period, string $display, string $storage): void
    {
        $row = [
            'gto_id_token' => 1,
            'gto_valid_from' => $from,
            'gto_valid_until' => '2023-03-04 23:59:59',
            'gro_valid_after_unit' => $period,
            'gro_valid_for_unit' => 'D'
        ];
        $model  = $this->getModelLoaded([$row]);
        $data = $model->loadFirst(['gto_id_token' => 1]);
        // print_r($data);

        $metaModel = $model->getMetaModel();

        // Check onLoad
        $this->assertInstanceOf(\DateTimeImmutable::class, $data['gto_valid_from']);

        // Check display value
        $bridge = $model->getBridgeFor('display');
        $this->assertEquals($display, $bridge->format('gto_valid_from', $data['gto_valid_from']));

        // Check storage of original data (no change)
        $save = $metaModel->processRowBeforeSave($data);
        $this->assertEquals($from, $save['gto_valid_from']);

        // Mimioc post
        $data['gto_valid_from'] = $display;
        $post = $model->loadPostData($data, false);
        $this->assertInstanceOf(\DateTimeImmutable::class, $post['gto_valid_from']);
        $this->assertEquals($display, $bridge->format('gto_valid_from', $post['gto_valid_from']));

        // Save post
        $save = $metaModel->processRowBeforeSave($post);
        $this->assertEquals($storage, $save['gto_valid_from']);
    }
}