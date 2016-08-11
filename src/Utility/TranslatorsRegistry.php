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
    /**
     * The class' instance.
     *
     * @var TranslatorsRegistry
     */
    protected static $_instance = null;

    /**
     * The default translator alias.
     *
     * @var string
     */
    protected $_default = null;

    /**
     * The default translator name.
     *
     * @var string
     */
    protected $_defaultTranslator = 'Translator.Translator';

//    use EventDispatcherTrait;

//    protected function __construct()
//    {
//        $this->_eventManager = new EventManager();
//    }

    /**
     * Returns the instance of the registry.
     *
     * @return Translator\Utility\TranslatorsRegistry
     */
    public static function getinstance()
    {
        if (null === self::$_instance) {
            $className = get_called_class();
            self::$_instance = new $className;
        }

        return self::$_instance;
    }

    /**
     * Clears the instance of the registry.
     *
     * @return void
     */
    public static function clear()
    {
        if (null !== self::$_instance) {
            self::$_instance->reset();
            self::$_instance = null;
        }
    }

    /**
     * Should resolve the classname for a given object type.
     *
     * @param string $class The class to resolve.
     * @return string|false The resolved name or false for failure.
     */
    protected function _resolveClassName($class)
    {
        return App::className($class, 'Utility');
    }

    /**
     * Throw an exception when the requested object name is missing.
     *
     * @param string $class The class that is missing.
     * @param string $plugin The plugin $class is missing from.
     * @return void
     * @throws \RuntimeException
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        $msg = sprintf(__d('cake_dev', 'Missing utility class %s'), ltrim("{$plugin}.{$class}", '.'));
        throw new \RuntimeException($msg, 500);
    }

    /**
     * Returns the first loaded translator object or an instance of the default
     * translator.
     *
     * @see $_defaultTranslator
     *
     * @return \Translator\Utility\TranslatorInterface
     */
    public function getDefault()
    {
        if (0 === count($this->loaded())) {
            $this->load(
                str_replace('.', '', $this->_defaultTranslator),
                ['className' => $this->_defaultTranslator]
            );
        }

        return $this->get($this->_default);
    }

    /**
     * Loads/constructs a translator instance.
     *
     * @todo $config['domains'] ?
     *
     * @param string $objectName The name/class of the object to load.
     * @param array $config Additional settings to use when loading the object.
     * @return \Translator\Utility\TranslatorInterface
     */
    public function load($objectName, $config = [])
    {
        if (0 === count($this->loaded())) {
            $this->_default = $objectName;
        }

        $result = parent::load($objectName, $config);

//        $this->_eventManager->on($result);//FIXME: Cake\Event\EventListenerInterface;, check if already loaded
//        $this->dispatchEvent('Translator.onLoad');
        return $result;
    }

    /**
     * Create an instance of a given classname.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string $class The class to build.
     * @param string $alias The alias of the object.
     * @param array $config The Configuration settings for construction
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
