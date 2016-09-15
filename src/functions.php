<?php
/**
 * Utility shorthand functions for the Translator plugin.
 */
use Translator\Utility\TranslatorsRegistry;

if (!function_exists('__m')) {
    /**
     * Returns a translated string if one is found; Otherwise, the submitted message.
     *
     * @param string $singular Text to translate.
     * @param mixed $args Array with arguments or multiple arguments in function.
     * @return string|null The translated text, or null if invalid.
     * @see http://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__
     */
    function __m($singular, $args = null)
    {
        if (!$singular) {
            return null;
        }

        $arguments = func_num_args() === 2 ? (array)$args : array_slice(func_get_args(), 1);
        $name = TranslatorsRegistry::defaultTranslator();
        return TranslatorsRegistry::getInstance()->get($name)->translate($singular, $arguments);
    }
}

if (!function_exists('__mn')) {
    /**
     * Returns correct plural form of message identified by $singular and $plural for count $count.
     * Some languages have more than one form for plural messages dependent on the count.
     *
     * @param string $singular Singular text to translate.
     * @param string $plural Plural text.
     * @param int $count Count.
     * @param mixed $args Array with arguments or multiple arguments in function.
     * @return string|null Plural form of translated string, or null if invalid.
     * @link http://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__n
     */
    function __mn($singular, $plural, $count, $args = null)
    {
        if (!$singular) {
            return null;
        }

        $arguments = func_num_args() === 4 ? (array)$args : array_slice(func_get_args(), 3);
        $name = TranslatorsRegistry::defaultTranslator();
        return TranslatorsRegistry::getInstance()->get($name)->translate(
            $plural,
            ['_count' => $count, '_singular' => $singular] + $arguments
        );
    }
}

if (!function_exists('__mx')) {
    /**
     * Returns a translated string if one is found; Otherwise, the submitted message.
     * The context is a unique identifier for the translations string that makes it unique
     * within the same domain.
     *
     * @param string $context Context of the text.
     * @param string $singular Text to translate.
     * @param mixed $args Array with arguments or multiple arguments in function.
     * @return string|null Translated string.
     * @link http://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__x
     */
    function __mx($context, $singular, $args = null)
    {
        if (!$singular) {
            return null;
        }

        $arguments = func_num_args() === 3 ? (array)$args : array_slice(func_get_args(), 2);
        $name = TranslatorsRegistry::defaultTranslator();
        return TranslatorsRegistry::getInstance()->get($name)->translate($singular, ['_context' => $context] + $arguments);
    }
}

if (!function_exists('__mxn')) {
    /**
     * Returns correct plural form of message identified by $singular and $plural for count $count.
     * Some languages have more than one form for plural messages dependent on the count.
     * The context is a unique identifier for the translations string that makes it unique
     * within the same domain.
     *
     * @param string $context Context of the text.
     * @param string $singular Singular text to translate.
     * @param string $plural Plural text.
     * @param int $count Count.
     * @param mixed $args Array with arguments or multiple arguments in function.
     * @return string|null Plural form of translated string.
     * @link http://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__xn
     */
    function __mxn($context, $singular, $plural, $count, $args = null)
    {
        if (!$singular) {
            return null;
        }

        $arguments = func_num_args() === 5 ? (array)$args : array_slice(func_get_args(), 2);
        $name = TranslatorsRegistry::defaultTranslator();
        return TranslatorsRegistry::getInstance()->get($name)->translate(
            $plural,
            ['_count' => $count, '_singular' => $singular, '_context' => $context] + $arguments
        );
    }
}
