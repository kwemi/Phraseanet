<?php

namespace Alchemy\Tests\Phrasea\Border;

use Alchemy\Phrasea\Border\MetadataBag;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Value\Multi;

/**
 * @group functional
 * @group legacy
 */
class MetadataBagTest extends \PhraseanetTestCase
{
    /**
     * @var MetadataBag
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new MetadataBag;
    }

    /**
     * @covers Alchemy\Phrasea\Border\MetadataBag::toMetadataArray
     */
    public function testToMetadataArray()
    {
        $structure = self::$DI['collection']->get_databox()->get_meta_structure();

        $valueMono = new Mono('mono value');
        $valueMulti = new Multi(['multi', 'value']);

        $monoAdded = $multiAdded = false;

        foreach ($structure as $databox_field) {
            if (!$monoAdded) {
                $this->object->set($databox_field->get_tag()->getTagname(), new Metadata($databox_field->get_tag(), $valueMono));
                $monoAdded = $databox_field->get_id();
            } elseif (!$multiAdded) {
                if ($databox_field->is_multi()) {
                    $this->object->set($databox_field->get_tag()->getTagname(), new Metadata($databox_field->get_tag(), $valueMulti));
                    $multiAdded = $databox_field->get_id();
                }
            } else {
                break;
            }
        }

        if (!$multiAdded || !$monoAdded) {
            $this->markTestSkipped('Unable to find multi value field');
        }

        $this->assertEquals([
            [
                'meta_struct_id' => $monoAdded,
                'value'          => 'mono value',
                'meta_id'        => null
            ],
            [
                'meta_struct_id' => $multiAdded,
                'value'          => 'multi',
                'meta_id'        => null
            ],
            [
                'meta_struct_id' => $multiAdded,
                'value'          => 'value',
                'meta_id'        => null
            ],
            ], $this->object->toMetadataArray($structure));
    }
}
