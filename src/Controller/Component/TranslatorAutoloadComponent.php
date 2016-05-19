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
     * @var array
     */
    public $defaultSettings = [
        'translatorClass' => '\\Translator\\Utility\\Translator'
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
        $Controller = $this->_registry->getController();

        if ($this->_domains === null) {
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

            $this->_cacheKey = "{$this->name}.{$pluginName}{$Controller->name}.{$Controller->action}";
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
            Hash::normalize($this->settings)
        );
    }

    /**
     * Imports the translation cache for the current domains.
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
     * Exports the translation cache for the current domains.
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
     * Loads the translations for the current domains before rendering the view.
     *
     * @param Event $event The event that caused the callback
     * @return void
     */
    public function beforeRender(Event $event)
    {
        $this->load();
    }

    /**
     * Caches the translations for the current domains after rendering the view.
     *
     * @param Event $event The event that caused the callback
     * @return void
     */
    public function shutdown(Event $event)
    {
        $this->save();
    }
}
