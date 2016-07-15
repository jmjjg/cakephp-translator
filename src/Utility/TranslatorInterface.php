<?php
/**
 * Source code for the Translator utility interface from the Translator plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Utility;

/**
 * The TranslatorInterface defines a singleton that can translate messages based
 * on language, a list of domains, live-caching, importing and exporting the cache.
 */
interface TranslatorInterface
{
    /**
     * Returns an instance of the translator class.
     *
     * @return TranslatorInterface
     */
    public static function getInstance();

    /**
     * Resets the internal state of the translator (domains, cache, ...).
     *
     * @return void
     */
    public static function reset();

    /**
     * Returns the current language currently used by the application.
     *
     * @return string
     */
    public static function lang();

    /**
     * Sets or returns the domains currently used by the translator.
     *
     * @param string|array $domains A (list of) domain name(s)
     * @return array
     */
    public static function domains($domains = null);

    /**
     * Returns the current key for the current translation domains.
     *
     * @return string
     */
    public static function domainsKey();

    /**
     * Returns the currently cached translations.
     *
     * @return array
     */
    public static function export();

    /**
     * Import cached translations, merging previously set cached entries.
     *
     * @param array $cache The cache content to import
     * @return void
     */
    public static function import(array $cache);

    /**
     * Returns true if new translations have been inserted into the cache.
     *
     * @return bool
     */
    public static function tainted();

    /**
     * Returns a translated string if one is found, otherwise, the submitted message.
     *
     * @see __()
     *
     * @param string $singular The message key.
     * @param array $values Token values to interpolate into the
     * message.
     * @return string The translated message with tokens replaced.
     */
    public static function __($singular, array $values = []);
}
