<?php
/**
 * Source code for the Translator.Translator utility class.
 *
 * @author Christian Buffin
 */
namespace Translator\Utility;

use Aura\Intl\FormatterLocator;
use Cake\I18n\Formatter\IcuFormatter;
use Cake\I18n\Formatter\SprintfFormatter;
use Cake\I18n\I18n;
use Translator\Utility\Storage;
use Translator\Utility\TranslatorInterface;

/**
 * The Translator class...
 */
class Translator implements TranslatorInterface
{

    /**
     * The key cache part for the current domains.
     *
     * @var string
     */
    protected static $_domainsKey = 'a:0:{}';

    /**
     * A list of domains to be checked.
     *
     * @var array
     */
    protected static $_domains = [];

    /**
     * A cache of already translated messages.
     *
     * @var array
     */
    protected static $_cache = [];

    /**
     * Indicate wether the cache content has changed or not.
     *
     * @var bool
     */
    protected static $_tainted = false;

    /**
     * A reference to the translator object.
     *
     * @var TranslatorInterface
     */
    protected static $_this = null;

    /**
     * An instance of the messages formatter.
     *
     * @var FormatterInterface
     */
    protected static $_formatter = null;

    /**
     * Protected constructor to force the usage of the static getInstance method.
     *
     * @return Translator\Utility\Translator
     */
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

        $instance::$_domainsKey = 'a:0:{}';
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
    public static function domainsKey()
    {
        $instance = self::getInstance();
//        return $instance::$_domainKey;
        return $instance::$_domainsKey;
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
                foreach ($keys as $key => $methods) {
                    foreach ($methods as $method => $messages) {
                        $path = [$lang, $key, $method];
                        $instance::$_cache = Storage::insert(
                            $instance::$_cache,
                            $path,
                            array_merge(
                                (array)Storage::get($instance::$_cache, $path),
                                $messages
                            )
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
     * {@inheritdoc}
     *
     * @see __()
     *
     * @param string $key The message key.
     * @param array $values Token values to interpolate into the
     * message.
     * @return string The translated message with tokens replaced.
     */
    public static function __($key, array $values = [])
    {
        $instance = self::getInstance();
        $key = (string)$key;

        $path = [$instance::lang(), $instance::$_domainsKey, __FUNCTION__, $key];
        if (Storage::exists($instance::$_cache, $path)) {
            $message = Storage::get($instance::$_cache, $path);
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

            $path = [$instance::lang(), $instance::$_domainsKey, __FUNCTION__, $key];
            $instance::$_cache = Storage::insert($instance::$_cache, $path, $message);
            $instance::$_tainted = true;
        }

        // C/P from CakePHP's Translator::translate()
        // are there token replacement values?
        if (! $values) {
            // no, return the message string as-is
            return $message;
        }

        // run message string through formatter to replace tokens with values
        return $instance::$_formatter->format($instance::lang(), $message, $values);
    }
}
