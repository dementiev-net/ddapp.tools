<?php

namespace DD\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use DD\Tools\Main;
use DD\Tools\Helpers\LogHelper;

class CustomMail
{
    private const CONNECTION_TIMEOUT = 5;
    private const TEST_CONNECTION_TIMEOUT = 5;
    private $mailer;
    private $settings;

    public function __construct()
    {
        // Настройка логирования
        LogHelper::configure();
        //LogHelper::error("cron", "cacheAgent error: " . $e->getMessage());
        //LogHelper::info("cron", "- Cache folder: " . FileHelper::formatBytes($folderCache));

        $this->loadSettings();
        $this->initMailer();
    }

    /**
     * Загрузка настроек
     * @return void
     */
    private function loadSettings(): void
    {
        $secure = match (Option::get(Main::MODULE_ID, "smtp_secure", "tls")) {
            0 => "",       // без авторизации
            1 => "ssl",    // ssl
            2 => "tls",    // tls
            default => "",
        };

        $this->settings = [
            "enabled" => Option::get(Main::MODULE_ID, "smtp_enabled", "N") === "Y",
            "smtp_host" => Option::get(Main::MODULE_ID, "smtp_host"),
            "smtp_port" => (int)Option::get(Main::MODULE_ID, "smtp_port"),
            "smtp_secure" => $secure,
            "smtp_username" => Option::get(Main::MODULE_ID, "smtp_login"),
            "smtp_password" => Option::get(Main::MODULE_ID, "smtp_password"),
            "from_email" => Option::get(Main::MODULE_ID, "smtp_email_sender"),
            "from_name" => Option::get(Main::MODULE_ID, "smtp_name_sender"),
            "dkim_enable" => Option::get(Main::MODULE_ID, "smtp_dkim_enabled", "N") === "Y",
            "dkim_domain" => Option::get(Main::MODULE_ID, "smtp_dkim_domain"),
            "dkim_private_key" => Option::get(Main::MODULE_ID, "smtp_dkim_private_key"),
            "dkim_selector" => Option::get(Main::MODULE_ID, "smtp_dkim_selector"),
            "dkim_passphrase" => Option::get(Main::MODULE_ID, "smtp_dkim_passphrase"),
            // Дополнительные настройки
            "charset" => "UTF-8",
            "debug_level" => 4, // 0-4
        ];

        $this->settings = [
            "smtp_host" => "server31.hosting.reg.ru",
            "smtp_port" => 465,
            "smtp_secure" => "SSL",
            "smtp_username" => "info@mit1a.ru",
            "smtp_password" => "Uf@VgtzT*CF7n]",
        ];
    }

    /**
     * Инициализация PHPMailer
     * @return void
     * @throws Exception
     */
    private function initMailer(): void
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->Timeout = static::CONNECTION_TIMEOUT;
        $this->mailer->getSMTPInstance()->Timelimit = static::CONNECTION_TIMEOUT;

        try {
            // Настройки сервера
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->settings["smtp_host"];
            $this->mailer->Port = $this->settings["smtp_port"];
            $this->mailer->CharSet = $this->settings["charset"];

            // Безопасность
            if (!empty($this->settings["smtp_secure"])) {
                $this->mailer->SMTPSecure = $this->settings["smtp_secure"];
            }

            // Аутентификация
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->settings["smtp_username"];
            $this->mailer->Password = $this->settings["smtp_password"];

            // Отправитель по умолчанию
            if (!empty($this->settings["from_email"])) {
                $this->mailer->setFrom($this->settings["from_email"], $this->settings["from_name"]);
            }

            // DKIM подпись
            if (
                $this->settings["dkim_enable"]
                && !empty($this->settings["dkim_domain"])
                && !empty($this->settings["dkim_private_key"])
            ) {
                $this->setupDKIM();
            }

            // Отладка
            if (!empty($this->settings["debug_level"]) && $this->settings["debug_level"] > 0) {
                $this->mailer->SMTPDebug = $this->settings["debug_level"];

                // Собираем отладку в массив
                $this->mailer->Debugoutput = function ($str, $level) {
                    $this->smtpDebugOutput[] = "[Level $level] " . trim($str);
                };
            }

        } catch (Exception $e) {
            $this->smtpDebugOutput[] = "Ошибка инициализации PHPMailer: " . $e->getMessage();
            // Пробросим дальше, чтобы перехватить в initMailer() или send()
            throw $e;
        }
    }

    /**
     * Настройка DKIM
     * @return void
     */
    private function setupDKIM(): void
    {
        try {
            $this->mailer->DKIM_domain = $this->settings["dkim_domain"];
            $this->mailer->DKIM_private = $this->settings["dkim_private_key"];
            $this->mailer->DKIM_selector = $this->settings["dkim_selector"];
            $this->mailer->DKIM_passphrase = $this->settings["dkim_passphrase"] ?? "";
            $this->mailer->DKIM_identity = $this->mailer->From;
        } catch (Exception $e) {
            $this->smtpDebugOutput[] = "Ошибка настройки DKIM: " . $e->getMessage();
            // Пробросим дальше, чтобы перехватить в initMailer() или send()
            throw $e;
        }
    }

    /**
     * Отправка письма
     * @param $params
     * @return array
     */
    public function send($params = []): array
    {
        $this->smtpDebugOutput = []; // Очистим перед отправкой

        try {
            // Очистка предыдущих данных
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            // Получатели
            if (isset($params["to"])) {
                if (is_array($params["to"])) {
                    foreach ($params["to"] as $email => $name) {
                        if (is_numeric($email)) {
                            $this->mailer->addAddress($name);
                        } else {
                            $this->mailer->addAddress($email, $name);
                        }
                    }
                } else {
                    $this->mailer->addAddress($params["to"]);
                }
            }

            // Копии
            if (!empty($params["cc"])) {
                foreach ((array)$params["cc"] as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addCC($name);
                    } else {
                        $this->mailer->addCC($email, $name);
                    }
                }
            }

            // Скрытые копии
            if (!empty($params["bcc"])) {
                foreach ((array)$params["bcc"] as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addBCC($name);
                    } else {
                        $this->mailer->addBCC($email, $name);
                    }
                }
            }

            // Отправитель
            if (!empty($params["from"])) {
                $fromName = $params["from_name"] ?? "";
                $this->mailer->setFrom($params["from"], $fromName);
            }

            // Тема письма
            $this->mailer->Subject = $params["subject"] ?? "";

            // Тело письма
            if (!empty($params["html_body"])) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $params["html_body"];
                $this->mailer->AltBody = $params["text_body"] ?? "";
            } elseif (!empty($params["text_body"])) {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $params["text_body"];
            } elseif (!empty($params["body"])) {
                $this->mailer->isHTML(strip_tags($params["body"]) !== $params["body"]);
                $this->mailer->Body = $params["body"];
            }

            // Вложения
            if (!empty($params["attachments"])) {
                foreach ((array)$params["attachments"] as $attachment) {
                    if (is_string($attachment)) {
                        $this->mailer->addAttachment($attachment);
                    } elseif (is_array($attachment)) {
                        $this->mailer->addAttachment(
                            $attachment["path"] ?? "",
                            $attachment["name"] ?? "",
                            $attachment["encoding"] ?? "base64",
                            $attachment["type"] ?? ""
                        );
                    }
                }
            }

            // Заголовки
            if (!empty($params["headers"])) {
                foreach ((array)$params["headers"] as $name => $value) {
                    $this->mailer->addCustomHeader($name, $value);
                }
            }

            // Приоритет
            if (isset($params["priority"])) {
                $this->mailer->Priority = (int)$params["priority"];
            }

            // Отправка
            $result = $this->mailer->send();

            if ($result) {
                return [
                    "success" => true,
                    "message" => "Письмо успешно отправлено",
                    "debug" => $this->smtpDebugOutput
                ];
            }

            return [
                "success" => false,
                "message" => "Неизвестная ошибка при отправке письма",
                "debug" => $this->smtpDebugOutput
            ];

        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage(),
                "debug" => $this->smtpDebugOutput
            ];
        }
    }

    /**
     * Тестирование соединения
     * @return array
     */
    public function testConnection(): array
    {
        $debugOutput = [];

        try {
            $smtp = new SMTP();
            $smtp->Timeout = static::TEST_CONNECTION_TIMEOUT;
            $smtp->Timelimit = static::TEST_CONNECTION_TIMEOUT;
            $smtp->do_debug = SMTP::DEBUG_CONNECTION;

            // Логгируем отладку
            $smtp->setDebugOutput(function ($str, $level) use (&$debugOutput) {
                $debugOutput[] = trim($str);
            });

            // Подключение
            if (!$smtp->connect($this->settings["smtp_host"], $this->settings["smtp_port"], 30)) {
                return [
                    "success" => false,
                    "message" => "Не удалось подключиться к SMTP серверу",
                    "debug" => $debugOutput
                ];
            }

            // Аутентификация
            if (!$smtp->authenticate($this->settings["smtp_username"], $this->settings["smtp_password"])) {
                $smtp->quit();
                return [
                    "success" => false,
                    "message" => "Ошибка аутентификации SMTP",
                    "debug" => $debugOutput
                ];
            }

            $smtp->quit();

            return [
                "success" => true,
                "message" => "Соединение с SMTP сервером успешно установлено",
                "debug" => $debugOutput
            ];

        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage(),
                "debug" => $debugOutput
            ];
        }
    }
}