<?php
/**
 * Source code for the TranslatorAutoloadComponentTest unit test class from the Translator CakePHP 3 plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Test\TestCase\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
use Translator\Controller\Component\TranslatorAutoloadComponent;
use Translator\Utility\Translator;

/**
 * The TranslatorAutoloadComponentTest class unit tests the
 * Translator\Controller\Component\TranslatorAutoloadComponent class.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class TranslatorAutoloadComponentTest extends TestCase
{
    protected $Controller = null;

    protected $locales = null;

    /**
     *
     * @param array $requestParams
     * @param array $mockMethods
     * @param array $componentSettings
     */
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

    /**
     *
     */
    public function tearDownTranslator()
    {
        Translator::reset();
        unset($this->Controller->Translator, $this->Controller);
    }

    /**
     * Prepare before test method.
     */
    public function setUp()
    {
        parent::setUp();

        $this->locales = Configure::read('App.paths.locales');
        $locales = Plugin::classPath('Translator') . DS . '..' . DS . 'tests' . DS . 'Locale' . DS;
        Configure::write('App.paths.locales', $locales);
    }

    /**
     * Cleanup after test method.
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::write('App.paths.locales', $this->locales);
        $this->tearDownTranslator();
    }

    /**
     * Test that the beforeFilter, startup, beforeRender, beforeRedirect,
     * shutdown events will be redirected to the dispatchEvent method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::implementedEvents
     */
    public function testImplementedEvents()
    {
        $this->setUpTranslator();
        $expected = [
            'Controller.initialize' => 'dispatchEvent',
            'Controller.startup' => 'dispatchEvent',
            'Controller.beforeRender' => 'dispatchEvent',
            'Controller.beforeRedirect' => 'dispatchEvent',
            'Controller.shutdown' => 'dispatchEvent'
        ];
        $this->assertEquals($expected, $this->Controller->Translator->implementedEvents());
    }

    /**
     * Test that an exception is throw when calling an undefined method other than
     * beforeFilter, startup, beforeRender, beforeRedirect, shutdown.
     *
     * @expectedException        \Cake\Error\FatalErrorException
     * @expectedExceptionMessage Call to undefined method Translator\Controller\Component\TranslatorAutoloadComponent::foo()
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::__call
     */
    public function testUndefinedMethod()
    {
        $this->setUpTranslator();
        $this->assertNull($this->Controller->Translator->foo());
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
            'translatorClass' => '\\Translator\\Utility\\Translator',
            'events' => [
                'Controller.initialize' => 'load',
                'Controller.startup' => null,
                'Controller.beforeRender' => null,
                'Controller.beforeRedirect' => 'save',
                'Controller.shutdown' => 'save'
            ]
        ];
        $this->assertEquals($expected, $this->Controller->Translator->config());

        // 2. Overwrite default settings
        $config = [
            'translatorClass' => '\Foo\Utility\Translator'
        ] + $this->Controller->Translator->config();
        $this->Controller->Translator->initialize($config);
        $this->assertEquals($config, $this->Controller->Translator->config());
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
        $translatorClass = $this->Controller->Translator->config('translatorClass');
        $Instance = $translatorClass::getInstance();

        $this->Controller->Translator->load();

        $this->assertEquals([], $Instance->export());
    }

    /**
     * Test of the TranslatorAutoloadComponent::load().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::load
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::_translator
     */
    public function testLoadFromCache()
    {
        $this->setUpTranslator();
        $translatorClass = $this->Controller->Translator->config('translatorClass');
        $Instance = $translatorClass::getInstance();

        $cache = [
            'fr_FR' => [
                    'a:0:{}' => [
                            '__' => [
                                    'name' => 'name'
                            ]
                    ]
            ]
        ];

        Cache::write($this->Controller->Translator->cacheKey(), $cache);

        $this->Controller->Translator->load();

        $this->assertEquals($cache, $Instance->export());
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
        $translatorClass = $this->Controller->Translator->config('translatorClass');
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
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::__call
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::dispatchEvent
     */
    public function testControllerBeforeRender()
    {
        $this->setUpTranslator([], ['load', 'save'], ['events' => ['Controller.beforeRender' => 'load']]);

        $translatorClass = $this->Controller->Translator->config('translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('load');

        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->Controller->Translator->beforeRender($event);
    }

    /**
     * Test of the TranslatorAutoloadComponent::shutdown() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::__call
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::dispatchEvent
     */
    public function testControllerShutdown()
    {
        $this->setUpTranslator([], ['load', 'save'], ['events' => ['Controller.shutdown' => 'save']]);

        $translatorClass = $this->Controller->Translator->config('translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('save');

        $event = new Event('Controller.shutdown', $this->Controller);
        $this->Controller->Translator->shutdown($event);
    }

    /**
     * Test of the TranslatorAutoloadComponent::startup() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::__call
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::dispatchEvent
     */
    public function testControllerStartup()
    {
        $this->setUpTranslator([], ['load', 'save'], ['events' => ['Controller.startup' => 'load']]);

        $translatorClass = $this->Controller->Translator->config('translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('load');

        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Translator->startup($event);
    }

    /**
     * Test of the TranslatorAutoloadComponent::beforeFilter() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::__call
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::dispatchEvent
     */
    public function testControllerBeforeFilter()
    {
        $this->setUpTranslator([], ['load', 'save'], ['events' => ['Controller.initialize' => 'load']]);

        $translatorClass = $this->Controller->Translator->config('translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('load');

        $event = new Event('Controller.initialize', $this->Controller);
        $this->Controller->Translator->beforeFilter($event);
    }

    /**
     * Test of the TranslatorAutoloadComponent::beforeRedirect() method.
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::__call
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::dispatchEvent
     */
    public function testControllerBeforeRedirect()
    {
        $this->setUpTranslator([], ['load', 'save'], ['events' => ['Controller.beforeRedirect' => 'save']]);

        $translatorClass = $this->Controller->Translator->config('translatorClass');
        $Instance = $translatorClass::getInstance();
        $Instance->__('name');

        $this->Controller->Translator->expects($this->once())->method('save');

        $event = new Event('Controller.beforeRedirect', $this->Controller);
        $this->Controller->Translator->beforeRedirect($event);
    }
}
