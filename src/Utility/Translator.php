<?php
/**
 * Source code for the Translator.Translator utility class.
 */
namespace Translator\Utility;

use Aura\Intl\FormatterLocator;
use Cake\I18n\Formatter\IcuFormatter;
use Cake\I18n\Formatter\SprintfFormatter;
use Cake\I18n\I18n;
use Translator\Utility\TranslatorInterface;

/**
 * The Translator class...
 */
class Translator implements TranslatorInterface
{

    protected static $_domainsKey = null;

    protected static $_domains = [];

    protected static $_cache = [];

    protected static $_tainted = false;

    protected static $_this = null;

    protected static $_formatter = null;

    protected function __construct()
    {
        self::$_this = $this;

        // TODO
        /*$formatter = new FormatterLocator([
            'sprintf' => function () {
                return new SprintfFormatter;
            },
            'default' => function () {
                return new IcuFormatter;
            },
        ]);*/
        self::$_formatter = new IcuFormatter();
    }

    /**
     * {@inheritdoc}
     *
     * @return TranslatorInterface
     */
    public static function getInstance()
    {
        if (self::$_this === null) {
            $className = get_called_class();
            self::$_this = new $className;
        }

        return self::$_this;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public static function reset()
    {
        $instance = self::getInstance();

        $instance::$_domainsKey = null;
        $instance::$_domains = [];
        $instance::$_cache = [];
        $instance::$_tainted = false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function lang()
    {
        return I18n::locale();
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $domains A (list of) domain name(s)
     * @return array
     */
    public static function domains($domains = null)
    {
        $instance = self::getInstance();

        if ($domains === null) {
            return $instance::$_domains;
        } else {
            $instance::$_domains = array_values((array)$domains);
            $instance::$_domainsKey = serialize($instance::$_domains);

            return $instance::$_domains;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function domainKey()
    {
        $instance = self::getInstance();
        return $instance::$_domainKey;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function export()
    {
        $instance = self::getInstance();
        return $instance::$_cache;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $cache The cache content to import
     * @return void
     */
    public static function import(array $cache)
    {
        $instance = self::getInstance();

        if (empty($instance::$_cache)) {
            $instance::$_cache = $cache;
        } else {
            foreach ($cache as $lang => $keys) {
                if (!isset($instance::$_cache[$lang])) {
                    $instance::$_cache[$lang] = [];
                }
                foreach ($keys as $key => $methods) {
                    if (!isset($instance::$_cache[$lang][$key])) {
                        $instance::$_cache[$lang][$key] = [];
                    }
                    foreach ($methods as $method => $messages) {
                        if (!isset($instance::$_cache[$lang][$key][$method])) {
                            $instance::$_cache[$lang][$key][$method] = [];
                        }
                        $instance::$_cache[$lang][$key][$method] = array_merge(
                            $instance::$_cache[$lang][$key][$method],
                            $messages
                        );
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function tainted()
    {
        $instance = self::getInstance();
        return $instance::$_tainted;
    }

    /**
     * Stores the translation for the method name and key in the cache (using the
     * current language and domains keys) and marks the cache as tainted.
     *
     * @param string $method The method name
     * @param string $singular The message key
     * @param string $translation The translation to store
     * @return void
     */
    protected static function _setTranslation($method, $singular, $translation)
    {
        $instance = self::getInstance();
        $instance::$_tainted = true;

        $lang = $instance::lang();

        if (!isset($instance::$_cache[$lang])) {
            $instance::$_cache[$lang] = [];
        }
        if (!isset($instance::$_cache[$lang][$instance::$_domainsKey])) {
            $instance::$_cache[$lang][$instance::$_domainsKey] = [];
        }
        if (!isset($instance::$_cache[$lang][$instance::$_domainsKey][$method])) {
            $instance::$_cache[$lang][$instance::$_domainsKey][$method] = [];
        }

        $instance::$_cache[$lang][$instance::$_domainsKey][$method][$singular] = $translation;
    }

    /**
     * Checks the cache for the method name and key (using the current language
     * and domains keys).
     *
     * @param string $method The method name
     * @param string $singular The message key
     * @return bool
     */
    protected static function _issetTranslation($method, $singular)
    {
        $instance = self::getInstance();
        return isset($instance::$_cache[$instance::lang()][$instance::$_domainsKey][$method][$singular]);
    }

    /**
     * Returns the cached translation for the method name and key (using the
     * current language and domains keys).
     *
     * @param string $method The method name
     * @param string $singular The message key
     * @return string
     */
    protected static function _getTranslation($method, $singular)
    {
        $instance = self::getInstance();
        return $instance::$_cache[$instance::lang()][$instance::$_domainsKey][$method][$singular];
    }

    /**
     * {@inheritdoc}
     *
     * @see __()
     *
     * @param string $singular The message key.
     * @param array $tokens_values Token values to interpolate into the
     * message.
     * @return string The translated message with tokens replaced.
     */
    public static function __($key, array $tokens_values = [])
    {
        $instance = self::getInstance();
        $key = (string)$key;

        if ($instance::_issetTranslation(__FUNCTION__, $key)) {
            $message = $instance::_getTranslation(__FUNCTION__, $key);
        } else {
            $domains = $instance::domains();
            $count = count($domains);
            $message = $key;

            for ($i = 0; $i < $count && ($message === $key); $i++) {
                $message = I18n::translator($domains[$i])->translate($key);
            }

            if ($message === $key) {
                $message = I18n::translator()->translate($key);
            }

            $instance::_setTranslation(__FUNCTION__, $key, $message);
        }

        // C/P from CakePHP's Translator::translate()
        // are there token replacement values?
        if (! $tokens_values) {
            // no, return the message string as-is
            return $message;
        }

        // run message string through formatter to replace tokens with values
        return $instance::$_formatter->format($instance::lang(), $message, $tokens_values);
    }
}
