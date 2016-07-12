<?php

/**
 * Source code for the Translator.TranslatorAutoload component class.
 */
namespace Translator\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * The TranslatorAutoloadComponent class automatically loads and saves in the cache
 * the latest translations used for the current URL's domains.
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
     * Actual settings.
     *
     * @var array
     */
    public $settings = [];

    /**
     * Default settings.
     *
     * To get the translations available only in the view and saved after
     * rendering:
     * <code>
     * 'events' => [
     *  'load' => ['Controller.beforeRender'],
     *  'save' => ['Controller.shutdown']
     * ]
     * </code>
     *
     * To get the translations available anywhere in the controller and in the
     * view and saved before redirection or after rendering:
     * <code>
     * 'events' => [
     *  'load' => ['Controller.initialize'],
     *  'save' => ['Controller.beforeRedirect', 'Controller.shutdown']
     * ]
     * </code>
     *
     * Available events:
     *  - Controller.initialize (Component.beforeFilter)
     *  - Controller.startup (Component.startup)
     *  - Controller.beforeRender (Component.beforeRender)
     *  - Controller.beforeRedirect (Component.beforeRedirect)
     *  - Controller.shutdown (Component.beforeRender)
     *
     * @var array
     */
    public $defaultSettings = [
        'translatorClass' => '\\Translator\\Utility\\Translator',
        'events' => [
            'load' => ['Controller.beforeRender'],
            'save' => ['Controller.shutdown']
        ]
    ];

    /**
     * Available events.
     *
     * @var array
     */
    protected $_availableEvents = [
        'Controller.initialize' => 'Component.beforeFilter',
        'Controller.startup' => 'Component.startup',
        'Controller.beforeRender' => 'Component.beforeRender',
        'Controller.beforeRedirect' => 'Component.beforeRedirect',
        'Controller.shutdown' => 'Component.beforeRender'
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
            $translatorClass = Hash::get($this->settings, 'translatorClass');

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
     * Setup the event callbacks or provide sane defaults.
     *
     * @return void
     */
    protected function _setupEvents()
    {
        foreach (array_keys($this->settings['events']) as $method) {
            $this->settings['events'][$method] = (array)$this->settings['events'][$method];

            // A component event that is unknown ?
            if (!isset($this->defaultSettings['events'][$method])) {
                unset($this->settings['events'][$method]);
            } else {
                $error = false;
                foreach ($this->settings['events'][$method] as $key => $name) {
                    if (!isset($this->_availableEvents[$name])) {
                        $error = true;
                        unset($this->settings['events'][$method][$key]);
                    }
                }
                if (empty($this->settings['events'][$method]) && true === $error) {
                    $this->settings['events'][$method] = (array)$this->defaultSettings['events'][$method];
                }
            }
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

        $this->settings = array_merge(
            Hash::normalize($this->defaultSettings),
            Hash::normalize($config)
        );

        $this->_setupEvents();
    }

    /**
     * Dispatch an avent and call the corresponding component method when needed.
     *
     * @param Event $event The event to dispatch
     * @return void
     */
    protected function _dispatchEvent(Event $event)
    {
        foreach (array_keys($this->settings['events']) as $method) {
            $found = in_array($event->name(), $this->settings['events'][$method]);
            if (false !== $found) {
                call_user_func([$this, $method]);
            }
        }
    }

    /**
     * Dispatch the "Controller.initialize" event.
     *
     * @fixme: Controller.initialize / Component.beforeFilter
     *
     * @param Event $event The event that caused the callback
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        $this->_dispatchEvent($event);
    }

    /**
     * Dispatch the "Controller.startup" event.
     *
     * @param Event $event The event that caused the callback
     * @return void
     */
    public function startup(Event $event)
    {
        $this->_dispatchEvent($event);
    }

    /**
     * Dispatch the "Controller.beforeRender" event.
     *
     * @param Event $event The event that caused the callback
     * @return void
     */
    public function beforeRender(Event $event)
    {
        $this->_dispatchEvent($event);
    }

    /**
     * Dispatch the "Controller.beforeRedirect" event.
     *
     * @param Event $event The event that caused the callback
     * @return void
     */
    public function beforeRedirect(Event $event)
    {
        $this->_dispatchEvent($event);
    }

    /**
     * Dispatch the "Controller.beforeRender" event.
     *
     * @param Event $event The event that caused the callback
     * @return void
     */
    public function shutdown(Event $event)
    {
        $this->_dispatchEvent($event);
    }
}
