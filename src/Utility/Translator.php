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
use Cake\Utility\Hash;
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
     * Formatter to be used.
     *
     * @see I18n::defaultFormatter()
     *
     * @var FormatterLocator
     */
    protected static $_formatters = null;

    /**
     * Protected constructor to force the usage of the static getInstance method.
     *
     * @return Translator\Utility\Translator
     */
    protected function __construct()
    {
        self::$_this = $this;

        self::$_formatters = new FormatterLocator([
            'sprintf' => function () {
                return new SprintfFormatter;
            },
            'default' => function () {
                return new IcuFormatter;
            }
        ]);
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

        $instance::$_domainsKey = '[]';
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
            $instance::$_domainsKey = json_encode($instance::$_domains);

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
     *
     * @param string $key The message key.
     * @param array $tokens Token values to interpolate into the
     * message.
     * @return array The array cache path for the key and tokens.
     */
    public static function path($key, array $tokens = [])
    {
        $instance = self::getInstance();
        $key = (string)$key;

        $params = [
            '_count' => isset($tokens['_count']) ? $tokens['_count'] : null,
            '_singular' => isset($tokens['_singular']) ? $tokens['_singular'] : null,
            '_context' => isset($tokens['_context']) ? $tokens['_context'] : null
        ];

        return [$instance::lang(), $instance::$_domainsKey, json_encode(Hash::filter($params)), $key];
    }

    /**
     * Translates the message indicated by they key, replacing token values
     * along the way.
     *
     * @see I18n::translate()
     *
     * @param string $key The message key.
     * @param array $tokens Token values to interpolate into the
     * message.
     * @return string The translated message with tokens replaced.
     */
    public static function translate($key, array $tokens = [])
    {
        $instance = self::getInstance();
        $key = (string)$key;

        $path = $instance::path($key, $tokens);
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

            $instance::$_cache = Storage::insert($instance::$_cache, $path, $message);
            $instance::$_tainted = true;
        }

        // C/P from CakePHP's Translator::translate()
        // are there token replacement values?
        if (! $tokens) {
            // no, return the message string as-is
            return $message;
        }

        // run message string through I18n default formatter to replace tokens with values
        return $instance::$_formatters->get(I18n::defaultFormatter())->format($instance::lang(), $message, $tokens);
    }
}
