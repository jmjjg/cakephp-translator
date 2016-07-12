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
use Cake\Event\Event;
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
    protected $Controller = null;

    protected $locales = null;

    public function setUpTranslator(array $requestParams = [], array $mockMethods = [], array $componentSettings = [])
    {
        $requestParams += [
            'plugin' => null,
            'controller' => 'posts',
            'action' => 'index',
            '_ext' => null,
            'pass' => []
        ];
        $url = ltrim("{$requestParams['plugin']}.{$requestParams['controller']}/{$requestParams['action']}", '.');

        $this->request = new Request($url);
        $this->request->params = $requestParams;
        $this->Controller = new Controller($this->request);
        $registry = new ComponentRegistry($this->Controller);

        if (empty($mockMethods)) {
            $this->Controller->Translator = new TranslatorAutoloadComponent($registry, $componentSettings);
        } else {
            $this->Controller->Translator = $this->getMock(
                '\Translator\Controller\Component\TranslatorAutoloadComponent',
                $mockMethods,
                [$registry, $componentSettings ]
            );
        }
    }

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
        unset($this->Controller->Translator, $this->Controller);
    }

    /**
     * Test of the TranslatorAutoloadComponent::domains() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::domains
     */
    public function testDomains()
    {
        $this->setUpTranslator();

        $expected = [
            'posts_index',
            'posts',
            'default'
        ];
        $this->assertEquals($expected, $this->Controller->Translator->domains());
    }

    /**
     * Test of the TranslatorAutoloadComponent::cacheKey() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::cacheKey
     */
    public function testCacheKey()
    {
        $this->setUpTranslator();

        $expected = 'TranslatorAutoload.posts.index';
        $this->assertEquals($expected, $this->Controller->Translator->cacheKey());
    }

    /**
     * Test of the TranslatorAutoloadComponent::initialize() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::initialize
     */
    public function testInitialize()
    {
        $this->setUpTranslator();

        // 1. Check default settings
        $this->Controller->Translator->initialize([]);
        $expected = [
            'translatorClass' => '\Translator\Utility\Translator'
        ] + $this->Controller->Translator->defaultSettings;
        $this->assertEquals($expected, $this->Controller->Translator->settings);

        // 2. Overwrite default settings
        $config = [
            'translatorClass' => '\Foo\Utility\Translator'
        ] + $this->Controller->Translator->defaultSettings;
        $this->Controller->Translator->initialize($config);
        $this->assertEquals($config, $this->Controller->Translator->settings);
    }

    /**
     * Test of the TranslatorAutoloadComponent::load().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::load
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testLoad()
    {
        $this->setUpTranslator();
        $translatorClass = Hash::get($this->Controller->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();

        $this->Controller->Translator->load();

        $this->assertEquals([], $Instance->export());
    }

    /**
     * Test of the TranslatorAutoloadComponent::load() method when the translator
     * class cannot be found.
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Missing utility class \Foo\Utility\Translator
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::load
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testLoadMissingUtilityClassException()
    {
        $this->setUpTranslator();

        $config = [
            'translatorClass' => '\Foo\Utility\Translator'
        ];
        $this->Controller->Translator->initialize($config);

        $this->Controller->Translator->load();
    }

    /**
     * Test of the TranslatorAutoloadComponent::load() method when the translator
     * class does not implement the Translator\Utility\TranslatorInterface.
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Utility class \Translator\Utility\Storage does not implement Translator\Utility\TranslatorInterface
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::load
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testLoadNotImplementsUtilityClassException()
    {
        $this->setUpTranslator();

        $config = [
            'translatorClass' => '\Translator\Utility\Storage'
        ];
        $this->Controller->Translator->initialize($config);

        $this->Controller->Translator->load();
    }

    /**
     * Test of the TranslatorAutoloadComponent::save() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::save
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testSave()
    {
        $this->setUpTranslator();
        $translatorClass = Hash::get($this->Controller->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();

        $Instance->__('name');

        $this->Controller->Translator->save();

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

    /**
     * Test of the TranslatorAutoloadComponent::beforeRender() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::beforeRender
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_dispatchEvent
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_setupEvents
     */
    public function testControllerBeforeRender()
    {
        $this->setUpTranslator([], ['load', 'save']);

        $translatorClass = Hash::get($this->Controller->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('load');

        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->Controller->Translator->beforeRender($event);
    }

    /**
     * Test of the TranslatorAutoloadComponent::shutdown() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::shutdown
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_dispatchEvent
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_setupEvents
     */
    public function testControllerShutdown()
    {
        $this->setUpTranslator([], ['load', 'save']);

        $translatorClass = Hash::get($this->Controller->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('save');

        $event = new Event('Controller.shutdown', $this->Controller);
        $this->Controller->Translator->shutdown($event);
    }

    /**
     * Test of the TranslatorAutoloadComponent::startup() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::startup
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_dispatchEvent
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_setupEvents
     */
    public function testControllerStartup()
    {
        $this->setUpTranslator([], ['load', 'save'], ['events' => ['load' => ['Controller.startup']]]);

        $translatorClass = Hash::get($this->Controller->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('load');

        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Translator->startup($event);
    }

    /**
     * Test of the TranslatorAutoloadComponent::beforeFilter() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::beforeFilter
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_dispatchEvent
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_setupEvents
     */
    public function testControllerBeforeFilter()
    {
        $this->setUpTranslator([], ['load', 'save'], ['events' => ['load' => ['Controller.initialize']]]);

        $translatorClass = Hash::get($this->Controller->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('load');

        $event = new Event('Controller.initialize', $this->Controller);
        $this->Controller->Translator->beforeFilter($event);
    }

    /**
     * Test of the TranslatorAutoloadComponent::beforeRedirect() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::beforeRedirect
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_dispatchEvent
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_setupEvents
     */
    public function testControllerBeforeRedirect()
    {
        $this->setUpTranslator([], ['load', 'save'], ['events' => ['save' => ['Controller.beforeRedirect']]]);

        $translatorClass = Hash::get($this->Controller->Translator->settings, 'translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('save');

        $event = new Event('Controller.beforeRedirect', $this->Controller);
        $this->Controller->Translator->beforeRedirect($event);
    }
}
