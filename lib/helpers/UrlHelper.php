<?php

namespace DD\Tools\Helpers;

class UrlHelper
{
    /**
     * Строит URL с параметрами
     * @param string $path
     * @param array $params
     * @return string
     */
    public static function build(string $path, array $params = []): string
    {
        $url = $path;

        if (!empty($params)) {
            $url .= "?" . http_build_query($params);
        }

        return $url;
    }

    /**
     * Получает текущий URL
     * @return string
     */
    public static function current(): string
    {
        return (isset($_SERVER["HTTPS"]) ? "https" : "http") .
            "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    }

    /**
     * Проверяет, является ли URL внешним
     * @param string $url
     * @return bool
     */
    public static function isExternal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host && $host !== $_SERVER["HTTP_HOST"];
    }
}