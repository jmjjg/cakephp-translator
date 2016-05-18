<?php
/**
 * Source code for the StorageTest unit test class from the Translator CakePHP 3 plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Translator\Utility\Storage;

/**
 * The StorageTest class unit tests the Translator\Utility\Storage class.
 */
class StorageTest extends TestCase
{
    /**
     * Test of the Storage::exists() exists.
     *
     * @covers Translator\Utility\Storage::insert
     */
    public function testExists()
    {
        $data = [
            'fr_FR' => [
                'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}' => [
                    '__' => [
                        'id' => 'Id'
                    ]
                ]
            ]
        ];

        $keys1 = ['fr_FR', 'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}', '__', 'id'];
        $this->assertTrue(Storage::exists($data, $keys1));

        $keys2 = ['fr_FR', 'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}'];
        $this->assertTrue(Storage::exists($data, $keys2));

        $keys3 = ['fr_FR', 'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}', '__', 'name'];
        $this->assertFalse(Storage::exists($data, $keys3));

        $keys4 = [];
        $this->assertFalse(Storage::exists($data, $keys4));
    }

    /**
     * Test of the Storage::insert() method.
     *
     * @covers Translator\Utility\Storage::insert
     */
    public function testInsert()
    {
        $data = [
            'fr_FR' => [
                'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}' => [
                    '__' => [
                        'id' => 'Id'
                    ]
                ]
            ]
        ];

        $keys = ['fr_FR', 'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}', '__', 'name'];
        $expected = [
            'fr_FR' => [
                'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}' => [
                    '__' => [
                        'id' => 'Id',
                        'name' => 'Nom'
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, Storage::insert($data, $keys, 'Nom'));
    }

    /**
     * Test of the Storage::get() method.
     *
     * @covers Translator\Utility\Storage::get
     */
    public function testGet()
    {
        $data = [
            'fr_FR' => [
                'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}' => [
                    '__' => [
                        'id' => 'Id'
                    ]
                ]
            ]
        ];

        $keys1 = ['fr_FR', 'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}', '__', 'id'];
        $this->assertEquals('Id', Storage::get($data, $keys1));

        $keys2 = ['fr_FR', 'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}', '__', 'name'];
        $this->assertEquals(null, Storage::get($data, $keys2));

        $keys3 = ['fr_FR', 'a:3:{i:0;s:12:"groups_index";i:1;s:6:"groups";i:2;s:7:"default";}', '__'];
        $this->assertEquals(['id' => 'Id'], Storage::get($data, $keys3));
    }
}
