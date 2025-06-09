<?php

namespace DD\Tools\Helpers;

class ArrayHelper
{
    /**
     * Извлекает значение из многомерного массива по пути
     * @param array $array
     * @param string $path
     * @param $default
     * @return array|mixed|null
     */
    public static function getValue(array $array, string $path, $default = null)
    {
        $keys = explode(".", $path);
        $current = $array;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Группирует массив по указанному ключу
     * @param array $array
     * @param string $key
     * @return array
     */
    public static function groupBy(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $item) {
            $groupKey = is_array($item) ? $item[$key] : $item->$key;
            $result[$groupKey][] = $item;
        }

        return $result;
    }

    /**
     * Извлекает колонку из массива
     * @param array $array
     * @param string $column
     * @return array
     */
    public static function column(array $array, string $column): array
    {
        return array_column($array, $column);
    }

    /**
     * Рекурсивно очищает массив от пустых значений
     * @param array $array
     * @return array
     */
    public static function removeEmpty(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::removeEmpty($value);
            }

            if (empty($array[$key]) && $array[$key] !== 0 && $array[$key] !== "0") {
                unset($array[$key]);
            }
        }

        return $array;
    }
}