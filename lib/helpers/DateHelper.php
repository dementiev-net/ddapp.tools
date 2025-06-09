<?php

namespace DD\Tools\Helpers;

use Bitrix\Main\Type\DateTime;

class DateHelper
{
    /**
     * Форматирует дату для пользователя
     * @param $date
     * @param string $format
     * @return string
     */
    public static function formatUserDate($date, string $format = "d.m.Y H:i:s"): string
    {
        if (!$date instanceof DateTime) {
            $date = new DateTime($date);
        }

        return $date->format($format);
    }

    /**
     * Возвращает начало и конец дня
     * @param $date
     * @return array|DateTime[]
     */
    public static function getDayBounds($date): array
    {
        if (!$date instanceof DateTime) {
            $date = new DateTime($date);
        }

        $start = clone $date;
        $start->setTime(0, 0, 0);

        $end = clone $date;
        $end->setTime(23, 59, 59);

        return [$start, $end];
    }

    /**
     * Проверяет, является ли дата рабочим днем
     * @param DateTime $date
     * @return bool
     */
    public static function isWorkingDay(DateTime $date): bool
    {
        $dayOfWeek = (int)$date->format("N");
        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    }
}