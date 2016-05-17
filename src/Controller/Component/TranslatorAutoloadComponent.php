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

class TranslatorAutoloadComponent extends Component
{
    public $name = 'TranslatorAutoload';

    public $settings = array();

    public $defaultSettings = array(
        'translatorClass' => '\\Translator\\Utility\\Translator'
    );
    protected $_domains = null;
    protected $_cacheKey = null;
    protected $_translator = null;

    public function domains()
    {
        $Controller =  $this->_registry->getController();

        if ($this->_domains === null) {
            $controllerName = Inflector::underscore(Hash::get($Controller->request->params, 'controller'));
            $actionName = Inflector::underscore(Hash::get($Controller->request->params, 'action'));
            $pluginName = ltrim(Inflector::underscore(Hash::get($Controller->request->params, 'plugin')) . '_', '_');

            $this->_domains = array_values(
                    array_unique(
                            array(
                                $pluginName . $controllerName . '_' . $actionName,
                                $controllerName . '_' . $actionName,
                                $pluginName . $controllerName,
                                $controllerName,
                                'default'
                            )
                    )
            );
        }

        return $this->_domains;
    }

    public function cacheKey()
    {
        if ($this->_cacheKey === null) {
            $Controller =  $this->_registry->getController();

            $pluginName = ltrim(Inflector::camelize(Hash::get($Controller->request->params, 'plugin')) . '.', '.');

            $this->_cacheKey = "{$this->name}.{$pluginName}{$Controller->name}.{$Controller->action}";
        }

        return $this->_cacheKey;
    }

    protected function _translator()
    {
        if ($this->_translator === null) {
            $translatorClass = Hash::get($this->settings, 'translatorClass');

            if(false === class_exists($translatorClass)) {
                $msg = sprintf(__d('cake_dev', 'Missing utility class %s'), $translatorClass);
                throw new \RuntimeException($msg, 500);
            }

            if(false === in_array('Translator\Utility\TranslatorInterface', class_implements($translatorClass))) {
                $msg = sprintf(__d('cake_dev', 'Utility class %s does not implement Translator\Utility\TranslatorInterface'), $translatorClass);
                throw new \RuntimeException($msg, 500);
            }


            $this->_translator = $translatorClass::getInstance();
        }

        return $this->_translator;
    }

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->settings = array_merge(
            Hash::normalize($this->defaultSettings),
            Hash::normalize($this->settings)
        );
    }

    public function load()
    {
        $translator = $this->_translator();

        $translator->domains($this->domains());
        $cacheKey = $this->cacheKey();
        $cache = Cache::read($cacheKey);

        if ($cache !== false) {
            debug( $cache );
            $translator->import($cache);
        }
    }

    public function save()
    {
        $translator = $this->_translator();

        if ($translator->tainted()) {
            $cacheKey = $this->cacheKey();
            $cache = $translator->export();
            Cache::write($cacheKey, $cache);
        }
    }

    public function beforeRender(Event $event)
    {
        $this->load();
    }

    public function shutdown(Event $event)
    {
        $this->save();
    }
}
?>