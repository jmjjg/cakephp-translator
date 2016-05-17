<?php
/**
 * Source code for the Translator.Translator utility interface.
 */
namespace Translator\Utility;

interface TranslatorInterface
{
    public static function getInstance();

    public static function reset();

    public static function lang();

    public static function domains($domains = null);

    public static function domainKey();

    public static function export();

    public static function import(array $cache);

    public static function tainted();

    public static function __($singular, array $tokens_values = []);
}
?>