<?php
/**
 * Source code for the TranslatorsRegistry utility class from the Translator plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Utility;

use Cake\Core\App;
use Cake\Core\ObjectRegistry;

//use Cake\Event\EventDispatcherInterface;
//use Cake\Event\EventDispatcherTrait;
//use Cake\Event\EventManager;

/**
 * The Translator class...
 *
 * Sample usage:
 * $TranslatorsRegistry = TranslatorsRegistry::getinstance();
 * $TranslatorsRegistry->load('AppTranslator', ['className' => 'App.Translator']);
 *
 * $debug = [
 *     'domains' => $TranslatorsRegistry->getDefault()->domains($this->TranslatorAutoload->domains()),
 *     'name' => $TranslatorsRegistry->getDefault()->translate('name'),
 *     'Foo.bar' => $TranslatorsRegistry->getDefault()->translate('Foo.bar')
 * ];
 * debug($debug);
 */
//implements EventDispatcherInterface
class TranslatorsRegistry extends ObjectRegistry
{
    protected static $_instance = null;

    protected $_default = null;

//    use EventDispatcherTrait;

    /**
     *
     * @return TranslatorsRegistry
     */
    public static function getinstance()
    {
        if (null === self::$_instance) {
            $className = get_called_class();
            self::$_instance = new $className;
        }

        return self::$_instance;
    }

//    protected function __construct()
//    {
//        $this->_eventManager = new EventManager();
//    }

    /**
     *
     * @param string $class
     * @return string
     */
    protected function _resolveClassName($class)
    {
        return App::className($class, 'Utility');
    }

    /**
     *
     * @param string $class
     * @param string $plugin
     * @return void
     * @throws \RuntimeException
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        $msg = sprintf(__d('cake_dev', 'Missing utility class %s'), ltrim("{$plugin}.{$class}", '.'));
        throw new \RuntimeException($msg, 500);
    }

    /**
     *
     * @return \Translator\Utility\TranslatorInterface
     */
    public function getDefault()
    {
        return $this->_default;
    }

    /**
     *
     * @todo $config['domains'] ?
     *
     * @param string $objectName
     * @param array $config
     * @return \Translator\Utility\TranslatorInterface
     */
    public function load($objectName, $config = [])
    {
        $result = parent::load($objectName, $config);

        if (null === $this->_default) {
            $this->_default = $result;
        }

//        $this->_eventManager->on($result);//FIXME: Cake\Event\EventListenerInterface;, check if already loaded
//        $this->dispatchEvent('Translator.onLoad');
        return $result;
    }

    /**
     *
     * @param string $class
     * @param string $alias
     * @param array $config
     * @return \Translator\Utility\TranslatorInterface
     * @throws \RuntimeException
     */
    protected function _create($class, $alias, $config)
    {
        if (false === in_array('Translator\Utility\TranslatorInterface', class_implements($class))) {
            $msg = sprintf(__d('cake_dev', 'Utility class %s does not implement Translator\Utility\TranslatorInterface'), $class);
            throw new \RuntimeException($msg, 500);
        }

        return $class::getInstance();
    }
}
