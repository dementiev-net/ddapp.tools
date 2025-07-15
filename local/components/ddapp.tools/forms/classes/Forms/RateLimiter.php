<?php
namespace DDAPP\Tools\Forms;

use DDAPP\Tools\Helpers\LogHelper;

class RateLimiter
{
    private $formId;
    private $limits = [
        "per_minute" => 5,
        "per_hour" => 30,
        "per_day" => 100
    ];

    public function __construct($formId, $customLimits = [])
    {
        $this->formId = $formId;
        $this->limits = array_merge($this->limits, $customLimits);
    }

    /**
     * Проверка лимитов отправки форм
     */
    public function checkLimits($ip = null)
    {
        $ip = $ip ?: $this->getClientIP();
        $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "";

        LogHelper::info("rate_limiter", "Checking rate limits", [
            "ip" => $ip,
            "form_id" => $this->formId,
            "user_agent" => substr($userAgent, 0, 100)
        ]);

        // Проверяем каждый временной интервал
        foreach ($this->limits as $period => $limit) {
            $count = $this->getSubmissionCount($ip, $period);

            if ($count >= $limit) {
                LogHelper::warning("rate_limiter", "Rate limit exceeded", [
                    "ip" => $ip,
                    "form_id" => $this->formId,
                    "period" => $period,
                    "limit" => $limit,
                    "current_count" => $count,
                    "user_agent" => substr($userAgent, 0, 100)
                ]);

                return [
                    "allowed" => false,
                    "message" => $this->getLimitMessage($period, $limit),
                    "retry_after" => $this->getRetryAfter($period)
                ];
            }
        }

        // Записываем попытку отправки
        $this->recordSubmission($ip);

        return ["allowed" => true];
    }

    /**
     * Получение количества отправок за период
     */
    private function getSubmissionCount($ip, $period)
    {
        $cacheKey = "ddapp_form_rate_" . md5($ip . "_" . $this->formId . "_" . $period);

        // Используем кеш Bitrix для хранения счетчиков
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheTime = $this->getCacheTime($period);

        if ($cache->initCache($cacheTime, $cacheKey)) {
            $data = $cache->getVars();
            return $data["count"] ?? 0;
        }

        return 0;
    }

    /**
     * Запись попытки отправки
     */
    private function recordSubmission($ip)
    {
        foreach ($this->limits as $period => $limit) {
            $cacheKey = "ddapp_form_rate_" . md5($ip . "_" . $this->formId . "_" . $period);
            $cache = \Bitrix\Main\Data\Cache::createInstance();
            $cacheTime = $this->getCacheTime($period);

            $count = 0;
            if ($cache->initCache($cacheTime, $cacheKey)) {
                $data = $cache->getVars();
                $count = $data["count"] ?? 0;
            }

            $cache->clean($cacheKey);
            $cache->forceRewriting(true);

            if ($cache->startDataCache($cacheTime, $cacheKey)) {
                $cache->endDataCache([
                    "count" => $count + 1,
                    "last_submission" => time(),
                    "ip" => $ip,
                    "form_id" => $this->formId
                ]);
            }
        }

        LogHelper::info("rate_limiter", "Submission recorded", [
            "ip" => $ip,
            "form_id" => $this->formId,
            "timestamp" => time()
        ]);
    }

    /**
     * Получение времени кеширования для периода
     */
    private function getCacheTime($period)
    {
        $times = [
            "per_minute" => 60,
            "per_hour" => 3600,
            "per_day" => 86400
        ];

        return $times[$period] ?? 3600;
    }

    /**
     * Получение сообщения о превышении лимита
     */
    private function getLimitMessage($period, $limit)
    {
        $periods = [
            "per_minute" => "в минуту",
            "per_hour" => "в час",
            "per_day" => "в день"
        ];

        $periodText = $periods[$period] ?? $period;
        return "Превышен лимит отправки форм: не более {$limit} {$periodText}. Попробуйте позже.";
    }

    /**
     * Получение времени до следующей попытки
     */
    private function getRetryAfter($period)
    {
        $times = [
            "per_minute" => 60,
            "per_hour" => 3600,
            "per_day" => 86400
        ];

        return $times[$period] ?? 3600;
    }

    /**
     * Получение IP клиента
     */
    private function getClientIP()
    {
        $ipKeys = ["HTTP_CF_CONNECTING_IP", "HTTP_X_FORWARDED_FOR", "HTTP_X_REAL_IP", "REMOTE_ADDR"];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(",", $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER["REMOTE_ADDR"] ?? "unknown";
    }

    /**
     * Получение статистики лимитов для IP
     */
    public function getStats($ip = null)
    {
        $ip = $ip ?: $this->getClientIP();
        $stats = [];

        foreach ($this->limits as $period => $limit) {
            $count = $this->getSubmissionCount($ip, $period);
            $stats[$period] = [
                "count" => $count,
                "limit" => $limit,
                "remaining" => max(0, $limit - $count),
                "percentage" => round(($count / $limit) * 100, 1)
            ];
        }

        return $stats;
    }
}