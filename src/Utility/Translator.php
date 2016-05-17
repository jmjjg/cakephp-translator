<?php
/**
 * Source code for the Translator.Translator utility class.
 */
namespace Translator\Utility;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Translator\Utility\TranslatorInterface;

/**
 * The Translator class...
 *
 * @todo interface
 */
class Translator implements TranslatorInterface
{

    protected static $_domainsKey = null;

    protected static $_domains = array();

    protected static $_cache = array();

    protected static $_tainted = false;

    protected static $_this = null;

    protected function __construct()
    {
        self::$_this = $this;
    }

    public static function getInstance()
    {
        if (self::$_this === null) {
            $className = get_called_class();
            self::$_this = new $className;
        }

        return self::$_this;
    }

    public static function reset()
    {
        self::$_domainsKey = null;
        self::$_domains = array();
        self::$_cache = array();
        self::$_tainted = false;
    }

    public static function lang()
    {
        // TODO: Configure, session, app.php defaultLocale ?
        $lang = ini_get('intl.default_locale');
        if($lang === null) {
            $lang = 'eng';
        }

        return $lang;
    }

    public static function domains($domains = null)
    {
        if ($domains === null) {
            return self::$_domains;
        }
        else {
            self::$_domains = array_values((array) $domains);
            self::$_domainsKey = serialize(self::$_domains);

            return self::$_domains;
        }
    }

    public static function domainKey()
    {
        return self::$_domainKey;
    }

    public static function export()
    {
        return self::$_cache;
    }

    public static function import(array $cache)
    {
        if (empty(self::$_cache)) {
            self::$_cache = $cache;
        }
        else {
            foreach ($cache as $lang => $keys) {
                if (!isset(self::$_cache[$lang])) {
                    self::$_cache[$lang] = array();
                }
                foreach ($keys as $key => $methods) {
                    if (!isset(self::$_cache[$lang][$key])) {
                        self::$_cache[$lang][$key] = array();
                    }
                    foreach ($methods as $method => $messages) {
                        if (!isset(self::$_cache[$lang][$key][$method])) {
                            self::$_cache[$lang][$key][$method] = array();
                        }
                        self::$_cache[$lang][$key][$method] = array_merge(
                                self::$_cache[$lang][$key][$method], $messages
                        );
                    }
                }
            }
        }
    }

    public static function tainted()
    {
        return self::$_tainted;
    }

    protected static function _setTranslation($method, $singular, $translation)
    {
        self::$_tainted = true;

        $lang = self::lang();

        if (!isset(self::$_cache[$lang])) {
            self::$_cache[$lang] = array();
        }
        if (!isset(self::$_cache[$lang][self::$_domainsKey])) {
            self::$_cache[$lang][self::$_domainsKey] = array();
        }
        if (!isset(self::$_cache[$lang][self::$_domainsKey][$method])) {
            self::$_cache[$lang][self::$_domainsKey][$method] = array();
        }

        self::$_cache[$lang][self::$_domainsKey][$method][$singular] = $translation;
    }

    protected static function _issetTranslation($method, $singular)
    {
        return isset(self::$_cache[self::lang()][self::$_domainsKey][$method][$singular]);
    }

    protected static function _getTranslation($method, $singular)
    {
        return self::$_cache[self::lang()][self::$_domainsKey][$method][$singular];
    }

    public static function __($key, array $tokens_values = [])
    {
        $key = (string)$key;

        if (self::_issetTranslation(__FUNCTION__, $key)) {
            $message = self::_getTranslation(__FUNCTION__, $key);
        }
        else {
            $domains = self::domains();
            $count = count($domains);
            $message = $key;

            for ($i = 0; $i < $count && ( $message === $key ); $i++) {
                $message = I18n::translator( $domains[$i] )->translate($key);
            }

            if ($message === $key) {
                $message = I18n::translator()->translate($key);
            }
        }

        self::_setTranslation(__FUNCTION__, $key, $message);

        // C/P from CakePHP's Translator::translate()
        // are there token replacement values?
        if (! $tokens_values) {
            // no, return the message string as-is
            return $message;
        }

        // run message string through formatter to replace tokens with values
        return I18n::translator()->translate($message, $tokens_values);
    }
}
?>