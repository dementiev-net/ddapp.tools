<?php

namespace DDAPP\Tools\Helpers;

use Bitrix\Main\Web\Uri;

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
        return $_SERVER["REQUEST_URI"];
    }

    /**
     * Удаляет параметр из URL
     * @param string $param
     * @return string
     */
    public static function deleteParams(string $param): string
    {
        $uri = new Uri(self::current());
        $uri->deleteParams([$param]);

        return "<script>
            if (window.history.replaceState) {
                window.history.replaceState({}, document.title, '" . $uri->getUri() . "');
            }
        </script>";
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