<?php
/**
 * Source code for the TranslatorAutoloadComponentTest unit test class from the Translator CakePHP 3 plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Translator\Controller\Component\TranslatorAutoloadComponent;
use Translator\Utility\Translator;

/**
 * The TranslatorAutoloadComponentTest class unit tests the Translator\Controller\Component\TranslatorAutoloadComponent class.
 */
class TranslatorAutoloadComponentTest extends TestCase
{
    protected $Translator = null;

    protected $locales = null;

    public function setUp()
    {
        parent::setUp();

        $this->locales = Configure::read('App.paths.locales');
        $locales = Plugin::classPath('Translator') . DS . '..' . DS . 'tests' . DS . 'Locale' . DS;
        Configure::write('App.paths.locales', $locales);

        $this->request = new Request('posts/index');
        $this->request->params = [
            'plugin' => null,
            'controller' => 'posts',
            'action' => 'index',
            '_ext' => null,
            'pass' => []
        ];
        $controller = new Controller($this->request);
        $registry = new ComponentRegistry($controller);
        $this->Translator = new TranslatorAutoloadComponent($registry, []);
    }

    public function tearDown()
    {
        parent::tearDown();
        Configure::write('App.paths.locales', $this->locales);
        Translator::reset();
        unset($this->Translator);
    }

    /**
     * Test of the TranslatorAutoloadComponent::domains().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::domains
     */
    public function testDomains()
    {
        $expected = [
            'posts_index',
            'posts',
            'default'
        ];
        $this->assertEquals($expected, $this->Translator->domains());
    }

    /**
     * Test of the TranslatorAutoloadComponent::cacheKey().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::cacheKey
     */
    public function testCacheKey()
    {
        $expected = 'TranslatorAutoload.posts.index';
        $this->assertEquals($expected, $this->Translator->cacheKey());
    }

    /**
     * Test of the TranslatorAutoloadComponent::initialize().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::initialize
     */
    public function testInitialize()
    {
        // 1. Check default settings
        $this->Translator->initialize([]);
        $expected = [
            'translatorClass' => '\Translator\Utility\Translator'
        ];
        $this->assertEquals($expected, $this->Translator->settings);

        // 2. Overwrite default settings
        $config = [
            'translatorClass' => '\Foo\Utility\Translator'
        ];
        $this->Translator->initialize($config);
        $this->assertEquals($config, $this->Translator->settings);
    }

    /**
     * Test of the TranslatorAutoloadComponent::load().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::load
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testLoad()
    {
        $translatorClass = Hash::get($this->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();

        $this->Translator->load();

        $this->assertEquals([], $Instance->export());
    }

    /**
     * Test of the TranslatorAutoloadComponent::load() when the translator class
     * cannot be found.
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Missing utility class \Foo\Utility\Translator
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::load
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testLoadMissingUtilityClassException()
    {
        $config = [
            'translatorClass' => '\Foo\Utility\Translator'
        ];
        $this->Translator->initialize($config);

        $this->Translator->load();
    }

    /**
     * Test of the TranslatorAutoloadComponent::load() when the translator class
     * cannot be found.
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Utility class \Translator\Utility\Storage does not implement Translator\Utility\TranslatorInterface
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::load
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testLoadNotImplementsUtilityClassException()
    {
        $config = [
            'translatorClass' => '\Translator\Utility\Storage'
        ];
        $this->Translator->initialize($config);

        $this->Translator->load();
    }

    /**
     * Test of the TranslatorAutoloadComponent::save().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::save
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testSave()
    {
        $translatorClass = Hash::get($this->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();

        $Instance->__('name');

        $this->Translator->save();

        $expected = [
            'fr_FR' => [
                    'a:0:{}' => [
                            '__' => [
                                    'name' => 'name'
                            ]
                    ]
            ]
        ];
        $this->assertEquals($expected, $Instance->export());
    }
}
