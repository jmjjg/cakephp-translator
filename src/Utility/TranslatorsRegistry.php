<?php
/**
 * Source code for the TranslatorsRegistry utility class from the Translator plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Utility;

use Cake\Core\App;
use Cake\Core\ObjectRegistry;

/**
 * The TranslatorsRegistry class...
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
class TranslatorsRegistry extends ObjectRegistry
{
    /**
     * The class' instance.
     *
     * @var TranslatorsRegistry
     */
    protected static $_instance = null;

    /**
     * The default translator name.
     *
     * @var string
     */
    protected $_default = 'Translator.Translator';

    /**
     * Returns the instance of the registry.
     *
     * @return \Translator\Utility\TranslatorsRegistry
     */
    public static function getinstance()
    {
        if (null === static::$_instance) {
            $className = get_called_class();
            static::$_instance = new $className;
        }

        return static::$_instance;
    }

    /**
     * Clears the instance of the registry.
     *
     * @return void
     */
    public static function clear()
    {
        if (null !== static::$_instance) {
            static::$_instance->reset();
            static::$_instance = null;
        }
    }

    /**
     * Sets the name of the default translator.
     * Defaults to 'Translator.Translator'
     *
     * If called with no arguments, it will return the currently configured value.
     *
     * @param string|null $name The name of the formatter to use.
     * @return string The name of the formatter.
     */
    public static function defaultTranslator($name = null)
    {
        $instance = static::getinstance();
        if (null !== $name) {
            $instance->_default = $name;
        }
        return $instance->_default;
    }

    /**
     * Get loaded object instance.
     *
     * @param string $name Name of object.
     * @return \Translator\Utility\TranslatorInterface|null Object instance if loaded else null.
     */
    public function get($name)
    {
        if (false === isset($this->_loaded[$name])) {
            $this->load($name);
        }
        return parent::get($name);
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
     * Loads/constructs a translator instance.
     *
     * @param string $objectName The name/class of the object to load.
     * @param array $config Additional settings to use when loading the object.
     * @return \Translator\Utility\TranslatorInterface
     */
    public function load($objectName, $config = [])
    {
        $config += ['className' => $objectName];
        return parent::load($objectName, $config);
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
