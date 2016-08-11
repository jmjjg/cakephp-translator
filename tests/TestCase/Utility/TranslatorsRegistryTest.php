<?php
/**
 * Source code for the TranslatorsRegistryTest unit test class from the Translator CakePHP 3 plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Test\TestCase\Utility;

//use Cake\Core\Configure;
//use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Translator\Utility\TranslatorsRegistry;

/**
 * The TranslatorTest class unit tests the Translator\Utility\TranslatorsRegistry
 *  class.
 */
class TranslatorsRegistryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        TranslatorsRegistry::clear();
    }

    public function tearDown()
    {
        parent::tearDown();

        TranslatorsRegistry::clear();
    }

    /**
     * Test of the TranslatorsRegistry::getInstance() method.
     *
     * @covers Translator\Utility\TranslatorsRegistry::getInstance
     */
    public function testgetInstance()
    {
        $result = TranslatorsRegistry::getInstance();
        $this->assertInstanceOf('Translator\Utility\TranslatorsRegistry', $result);
    }
}
