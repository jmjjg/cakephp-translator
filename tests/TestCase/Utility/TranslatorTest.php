<?php
/**
 * Source code for the TranslatorTest unit test class from the Translator CakePHP 3 plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Translator\Utility\Translator;

/**
 * The TranslatorTest class unit tests the Translator\Utility\Translator class.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class TranslatorTest extends TestCase
{
    protected $locales = null;

    public function setUp()
    {
        parent::setUp();

        $this->locales = Configure::read('App.paths.locales');
        $locales = Plugin::classPath('Translator') . DS . '..' . DS . 'tests' . DS . 'Locale' . DS;
        Configure::write('App.paths.locales', $locales);
    }

    public function tearDown()
    {
        parent::tearDown();
        Configure::write('App.paths.locales', $this->locales);
        Translator::reset();
    }

    /**
     * Test of the Translator::getInstance() method.
     *
     * @covers Translator\Utility\Translator::__construct
     * @covers Translator\Utility\Translator::getInstance
     */
    public function testgetInstance()
    {
        $result = Translator::getInstance();
        $this->assertInstanceOf('Translator\Utility\Translator', $result);
    }

    /**
     * Test of the Translator::lang() method.
     *
     * @covers Translator\Utility\Translator::lang
     */
    public function testLang()
    {
        $result = Translator::lang();
        $expected = 'fr_FR';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::domains() method.
     *
     * @covers Translator\Utility\Translator::domains
     */
    public function testDomains()
    {
        $domains = 'groups_index';
        $result = Translator::domains((array)$domains);
        $expected = ['groups_index'];
        $this->assertEquals($expected, $result);

        $result = Translator::domains();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::__() method.
     *
     * @covers Translator\Utility\Translator::__
     */
    public function testUnderscore()
    {
        Translator::domains(['groups_index', 'groups']);
        $this->assertEquals('Nom', Translator::__('name'));
        $this->assertEquals('Supprimer', Translator::__('/Groups/delete/{{id}}'));
        $this->assertEquals('groups_index.po', Translator::__('filename'));

        Translator::domains(['groups', 'groups_index']);
        $this->assertEquals('Nom', Translator::__('name'));
        $this->assertEquals('Supprimer', Translator::__('/Groups/delete/{{id}}'));
        $this->assertEquals('groups.po', Translator::__('filename'));

        $result = Translator::__('Some string with {0} {1}', ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        // Test Storage's live cache
        $result = Translator::__('Some string with {0} {1}', ['other', 'arguments']);
        $expected = 'Some string with other arguments';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::tainted() method.
     *
     * @covers Translator\Utility\Translator::tainted
     */
    public function testTainted()
    {
        Translator::domains(['groups_index', 'groups']);
        $this->assertFalse(Translator::tainted());

        Translator::__('name');
        $this->assertTrue(Translator::tainted());
    }

    /**
     * Test of the Translator::reset() method.
     *
     * @covers Translator\Utility\Translator::reset
     */
    public function testReset()
    {
        Translator::domains(['groups_index', 'groups']);
        $result = Translator::__('name');
        $expected = 'Nom';
        $this->assertEquals($expected, $result);

        Translator::reset();
        $result = Translator::__('name');
        $expected = 'name';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::domainsKey() method.
     *
     * @covers Translator\Utility\Translator::domainsKey
     */
    public function testDomainsKey()
    {
        Translator::domains(['groups_index', 'groups']);
        $result = Translator::domainsKey();
        $expected = 'a:2:{i:0;s:12:"groups_index";i:1;s:6:"groups";}';
        $this->assertEquals($expected, $result);

        Translator::reset();
        $result = Translator::domainsKey();
        $expected = 'a:0:{}';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::export() method.
     *
     * @covers Translator\Utility\Translator::export
     */
    public function testExport()
    {
        Translator::domains(['groups_index', 'groups']);

        Translator::__('name');
        Translator::__('Some string with {0}', ['arguments']);

        $result = Translator::export();
        $expected = [
            'fr_FR' => [
                'a:2:{i:0;s:12:"groups_index";i:1;s:6:"groups";}' => [
                    '__' => [
                        'name' => 'Nom',
                        'Some string with {0}' => 'Some string with {0}',
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::import() method.
     *
     * @covers Translator\Utility\Translator::import
     */
    public function testImport()
    {
        $cache = [
            'fr_FR' => [
                'a:2:{i:0;s:12:"groups_index";i:1;s:6:"groups";}' => [
                    '__' => [
                        'name' => 'Nom',
                    ]
                ]
            ]
        ];
        Translator::import($cache);
        Translator::domains(['groups_index', 'groups']);
        $result = Translator::__('name');
        $expected = 'Nom';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::import() method.
     *
     * @covers Translator\Utility\Translator::import
     */
    public function testMultipleImport()
    {
        $cache1 = [
            'fr_FR' => [
                'a:1:{i:0;s:13:"groups_index2";}' => [
                    '__' => [
                        'Group.name' => 'Nom'
                    ]
                ]
            ]
        ];
        $cache2 = [
            'fr_FR' => [
                'a:1:{i:0;s:13:"groups_index2";}' => [
                    '__' => [
                        'Group.id' => 'Id'
                    ]
                ]
            ]
        ];
        Translator::import($cache1);
        Translator::import($cache2);
        Translator::domains('groups_index2');
        $result = Translator::export();
        $expected = [
            'fr_FR' => [
                'a:1:{i:0;s:13:"groups_index2";}' => [
                    '__' => [
                        'Group.name' => 'Nom',
                        'Group.id' => 'Id'
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::__() method with the sprintf formatter.
     *
     * @covers Translator\Utility\Translator::__
     */
    public function testUnderscoreWithSprintfFormatter()
    {
        \Cake\I18n\I18n::defaultFormatter('sprintf');

        $result = Translator::__('Some string with %s %s', ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::__() method with a wrong formatter.
     *
     * @expectedException        \Aura\Intl\Exception\FormatterNotMapped
     * @expectedExceptionMessage sprintfX
     *
     * @covers Translator\Utility\Translator::__
     */
    public function testUnderscoreWithWrongFormatter()
    {
        \Cake\I18n\I18n::defaultFormatter('sprintfX');

        Translator::__('Some string with {0} {1}', ['multiple', 'arguments']);
    }
}
