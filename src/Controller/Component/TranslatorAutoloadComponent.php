<?php
/**
 * Source code for the Translator.TranslatorAutoload component class.
 *
 * @author Christian Buffin
 */
namespace Translator\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Error\FatalErrorException;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * The TranslatorAutoloadComponent class automatically loads and saves in the cache
 * the latest translations used for the current URL's domains.
 *
 * Config
 *
 * 1. translatorClass
 * The translatorClass needs to implement the Translator\Utility\TranslatorInterface.
 * Default: '\\Translator\\Utility\\Translator'
 *
 * 2. events
 *
 * To get the translations available anywhere in the controller and in the
 * view and saved before redirection or after rendering (the default):
 * <code>
 *  'events' => [
 *      'Controller.initialize' => 'load',
 *      'Controller.startup' => null,
 *      'Controller.beforeRender' => null,
 *      'Controller.beforeRedirect' => 'save',
 *      'Controller.shutdown' => 'save'
 * ]
 * </code>
 *
 * To get the translations available only in the view and saved after
 * rendering:
 * <code>
 * 'events' => [
 *      'Controller.initialize' => null,
 *      'Controller.startup' => null,
 *      'Controller.beforeRender' => 'load',
 *      'Controller.beforeRedirect' => 'save',
 *      'Controller.shutdown' => 'save'
 * ]
 * </code>
 *
 * Available events:
 *  - Controller.initialize (Component.beforeFilter)
 *  - Controller.startup (Component.startup)
 *  - Controller.beforeRender (Component.beforeRender)
 *  - Controller.beforeRedirect (Component.beforeRedirect)
 *  - Controller.shutdown (Component.beforeRender)
 */
class TranslatorAutoloadComponent extends Component
{
    /**
     * Name of the component.
     *
     * @var string
     */
    public $name = 'TranslatorAutoload';

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'translatorClass' => '\\Translator\\Utility\\Translator',
        'events' => [
            'Controller.initialize' => 'load',
            'Controller.startup' => null,
            'Controller.beforeRender' => null,
            'Controller.beforeRedirect' => 'save',
            'Controller.shutdown' => 'save'
        ]
    ];


    /**
     * Holds the regular CakePHP implement events for a component.
     *
     * @see \Cake\Controller\Component::implementedEvents
     *
     * @var array
     */
    protected $_eventMap = [
        'Controller.initialize' => 'beforeFilter',
        'Controller.startup' => 'startup',
        'Controller.beforeRender' => 'beforeRender',
        'Controller.beforeRedirect' => 'beforeRedirect',
        'Controller.shutdown' => 'shutdown',
    ];

    /**
     * The list of domains to send to the translator class.
     *
     * @var array
     */
    protected $_domains = null;

    /**
     * The cache key to load and save the cache.
     *
     * @var string
     */
    protected $_cacheKey = null;

    /**
     * The instance of the translator class.
     *
     * @var TranslatorInterface
     */
    protected $_translator = null;

    /**
     * Returns an array of domains to be checked for the current URL.
     *
     * @return array
     */
    public function domains()
    {
        if ($this->_domains === null) {
            $Controller = $this->_registry->getController();

            $controllerName = Inflector::underscore(Hash::get($Controller->request->params, 'controller'));
            $actionName = Inflector::underscore(Hash::get($Controller->request->params, 'action'));
            $pluginName = ltrim(Inflector::underscore(Hash::get($Controller->request->params, 'plugin')) . '_', '_');

            $this->_domains = array_values(
                array_unique(
                    [
                        $pluginName . $controllerName . '_' . $actionName,
                        $controllerName . '_' . $actionName,
                        $pluginName . $controllerName,
                        $controllerName,
                        'default'
                    ]
                )
            );
        }

        return $this->_domains;
    }

    /**
     * Returns the cache key to be used for the current URL.
     *
     * @return string
     */
    public function cacheKey()
    {
        if ($this->_cacheKey === null) {
            $Controller = $this->_registry->getController();

            $pluginName = ltrim(Inflector::camelize(Hash::get($Controller->request->params, 'plugin')) . '.', '.');
            $controllerName = Hash::get($Controller->request->params, 'controller');
            $actionName = Hash::get($Controller->request->params, 'action');

            $this->_cacheKey = "{$this->name}.{$pluginName}{$controllerName}.{$actionName}";
        }

        return $this->_cacheKey;
    }

    /**
     * Returns the translator object, initializing it if needed.
     *
     * @return TranslatorInterface
     * @throws \RuntimeException
     */
    protected function _translator()
    {
        if ($this->_translator === null) {
            $translatorClass = $this->config('translatorClass');

            if (false === class_exists($translatorClass)) {
                $msg = sprintf(__d('cake_dev', 'Missing utility class %s'), $translatorClass);
                throw new \RuntimeException($msg, 500);
            }

            if (false === in_array('Translator\Utility\TranslatorInterface', class_implements($translatorClass))) {
                $msg = sprintf(__d('cake_dev', 'Utility class %s does not implement Translator\Utility\TranslatorInterface'), $translatorClass);
                throw new \RuntimeException($msg, 500);
            }


            $this->_translator = $translatorClass::getInstance();
        }

        return $this->_translator;
    }

    /**
     * Import the translation cache for the current domains.
     *
     * @return void
     */
    public function load()
    {
        $translator = $this->_translator();

        $translator->domains($this->domains());
        $cacheKey = $this->cacheKey();
        $cache = Cache::read($cacheKey);

        if ($cache !== false) {
            $translator->import($cache);
        }
    }

    /**
     * Export the translation cache for the current domains.
     *
     * @return void
     */
    public function save()
    {
        $translator = $this->_translator();

        if ($translator->tainted()) {
            $cacheKey = $this->cacheKey();
            $cache = $translator->export();
            Cache::write($cacheKey, $cache);
        }
    }

    /**
     * Initialize the component configuration on startup.
     *
     * @param array $config The settings set in the controller
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->config($config);
    }

    /**
     * Dispatch an avent and call the corresponding component method when needed.
     *
     * @param Event $event The event to dispatch
     * @return void
     * @throws \RuntimeException
     */
    public function dispatchEvent(Event $event)
    {
        $events = $this->config('events');
        $method = isset($events[$event->name()]) ? $events[$event->name()] : null;

        if (true === in_array($method, ['load', 'save'])) {
            call_user_func([$this, $method]);
        } elseif (null !== $method) {
            $msg = sprintf(__d('cake_dev', 'Method "%s" cannot be called. Use one of "load", "save"'), $method);
            throw new \RuntimeException($msg, 500);
        }
    }

    /**
     * Redirect all the regular CakePHP implemented events for this component to the
     * dispatchEvent method.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Controller.initialize' => 'dispatchEvent',
            'Controller.startup' => 'dispatchEvent',
            'Controller.beforeRender' => 'dispatchEvent',
            'Controller.beforeRedirect' => 'dispatchEvent',
            'Controller.shutdown' => 'dispatchEvent'
        ];
    }

    /**
     * Dispatches the regular CakePHP implemented events for this component to the
     * dispatchEvent method.
     *
     * @param string $name The name of the method that was called
     * @param array $arguments The arguments the method was called with
     * @return mixed
     * @throws FatalErrorException
     */
    public function __call($name, $arguments)
    {
        if (in_array($name, $this->_eventMap)) {
            return call_user_func_array([$this, 'dispatchEvent'], $arguments);
        } else {
            $msg = sprintf('Call to undefined method %s::%s()', __CLASS__, $name);
            throw new FatalErrorException($msg);
        }
    }
}
