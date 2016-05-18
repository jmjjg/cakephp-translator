<?php
/**
 * Source code for the Translator.Storage utility class.
 */
namespace Translator\Utility;

/**
 * The Storage class provides a subset of functionalities from Cake's Hash utility
 * class (check, insert, get).
 * Provided simple paths are provided as array of keys, (meaningless) dots can be
 * used in paths.
 */
class Storage
{
    /**
     *
     * @param array $data The data to check
     * @param array $path The path keys to check
     * @return bool
     */
    public static function exists(array &$data, array $path)
    {
        if (empty($path)) {
            return false;
        }

        $current = &$data;
        foreach ($path as $key) {
            if (isset($current[$key])) {
                $current = &$current[$key];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @param array $data The data to insert to value into
     * @param array $path The path keys to check
     * @param mixed $value The value to insert
     * @return array
     */
    public static function insert(array $data, array $path, $value)
    {
        if (empty($path)) {
            return false;
        }

        $current = &$data;
        foreach ($path as $key) {
            if (false === isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        $current = $value;

        return $data;
    }

    /**
     *
     * @param array $data The data to get the value from
     * @param array $path The path keys to check
     * @param mixed $default The default value to return if the path keys do not exist
     * @return mixed
     */
    public static function get(array &$data, array $path, $default = null)
    {
        if (empty($path)) {
            return $default;
        }

        $current = &$data;
        foreach ($path as $key) {
            if (false === isset($current[$key])) {
                return $default;
            }
            $current = &$current[$key];
        }

        return $current;
    }
}
