<?php

namespace DDAPP\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use DDAPP\Tools\Main;
use DDAPP\Tools\Helpers\LogHelper;

Loc::loadMessages(__FILE__);

class CustomMail
{
    private const CONNECTION_TIMEOUT = 5;
    private const TEST_CONNECTION_TIMEOUT = 5;
    private $mailer;
    private $settings;
    private $smtpDebugOutput = [];

    public function __construct()
    {
        // Настройка логирования
        LogHelper::configure();

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
            throw $e;
        }
    }

    /**
     * Отправка письма в стиле mail()
     * @param string $to Получатель
     * @param string $subject Тема письма
     * @param string $message Сообщение
     * @param string $additional_headers Дополнительные заголовки
     * @param string $additional_parameters Дополнительные параметры
     * @return array
     */
    public function send($to, $subject, $message, $additional_headers = "", $additional_parameters = ""): array
    {
        $params = [
            "to" => $to,
            "subject" => $subject,
            "body" => $message
        ];

        // Парсинг дополнительных заголовков
        if (!empty($additional_headers)) {
            $headers = $this->parseHeaders($additional_headers);
            if (!empty($headers)) {
                $params["headers"] = $headers;
            }
        }

        return $this->mail($params);
    }

    /**
     * Парсинг заголовков из строки
     * @param string $headerString
     * @return array
     */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\n", $headerString);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $pos = strpos($line, ':');
            if ($pos === false) continue;

            $name = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Обработка специальных заголовков
            switch (strtolower($name)) {
                case 'from':
                    if (preg_match('/(.+?)\s*<(.+?)>/', $value, $matches)) {
                        $headers['from'] = $matches[2];
                        $headers['from_name'] = trim($matches[1], '"');
                    } else {
                        $headers['from'] = $value;
                    }
                    break;
                case 'cc':
                    $headers['cc'] = $this->parseEmailList($value);
                    break;
                case 'bcc':
                    $headers['bcc'] = $this->parseEmailList($value);
                    break;
                case 'content-type':
                    if (strpos(strtolower($value), 'text/html') !== false) {
                        $headers['is_html'] = true;
                    }
                    break;
                default:
                    $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Парсинг списка email адресов
     * @param string $emailList
     * @return array
     */
    private function parseEmailList(string $emailList): array
    {
        $emails = [];
        $parts = explode(',', $emailList);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            if (preg_match('/(.+?)\s*<(.+?)>/', $part, $matches)) {
                $emails[$matches[2]] = trim($matches[1], '"');
            } else {
                $emails[] = $part;
            }
        }

        return $emails;
    }

    /**
     * Отправка письма
     * @param array $params
     * @return array
     */
    public function mail($params = []): array
    {
        $this->smtpDebugOutput = [];

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
            if (!empty($params["from"]) || !empty($params["headers"]["from"])) {
                $from = $params["from"] ?? $params["headers"]["from"];
                $fromName = $params["from_name"] ?? $params["headers"]["from_name"] ?? "";
                $this->mailer->setFrom($from, $fromName);
            }

            // Тема письма
            $this->mailer->Subject = $params["subject"] ?? "";

            // Тело письма
            $isHtml = $params["headers"]["is_html"] ?? false;

            if (!empty($params["html_body"])) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $params["html_body"];
                $this->mailer->AltBody = $params["text_body"] ?? "";
            } elseif (!empty($params["text_body"])) {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $params["text_body"];
            } elseif (!empty($params["body"])) {
                $this->mailer->isHTML($isHtml || (strip_tags($params["body"]) !== $params["body"]));
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
                foreach ($params["headers"] as $name => $value) {
                    // Пропускаем специальные заголовки, которые уже обработаны
                    if (!in_array(strtolower($name), ['from', 'from_name', 'cc', 'bcc', 'is_html'])) {
                        $this->mailer->addCustomHeader($name, $value);
                    }
                }
            }

            // Приоритет
            if (isset($params["priority"])) {
                $this->mailer->Priority = (int)$params["priority"];
            }

            // Отправка
            $result = $this->mailer->mail();

            if ($result) {
                return [
                    "success" => true,
                    "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_SEND_OK"),
                    "debug" => $this->smtpDebugOutput
                ];
            }

            return [
                "success" => false,
                "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_UNKNOWN_ERROR"),
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
                    "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_SMTP_CONNECT_ERROR"),
                    "debug" => $debugOutput
                ];
            }

            // EHLO / HELO
            if (!$smtp->hello('localhost')) {
                $smtp->quit();
                return [
                    "success" => false,
                    "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_HELO_ERROR"),
                    "debug" => $debugOutput
                ];
            }

            // Получение расширений сервера
            $caps = $smtp->getServerExtList();

            // Если сервер поддерживает STARTTLS — выполнить
            if (isset($caps['STARTTLS'])) {
                if (!$smtp->startTLS()) {
                    $smtp->quit();
                    return [
                        "success" => false,
                        "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_STARTTLS_ERROR"),
                        "debug" => $debugOutput
                    ];
                }

                // Повторный EHLO после TLS
                if (!$smtp->hello('localhost')) {
                    $smtp->quit();
                    return [
                        "success" => false,
                        "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_STARTTLS_EHLO_ERROR"),
                        "debug" => $debugOutput
                    ];
                }

                $caps = $smtp->getServerExtList();
            }

            // Проверка, поддерживается ли AUTH
            if (!isset($caps['AUTH'])) {
                $smtp->quit();
                return [
                    "success" => false,
                    "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_AUTH_FALSE_ERROR"),
                    "debug" => $debugOutput
                ];
            }

            // Аутентификация
            if (!$smtp->authenticate($this->settings["smtp_username"], $this->settings["smtp_password"])) {
                $smtp->quit();
                return [
                    "success" => false,
                    "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_AUTH_ERROR"),
                    "debug" => $debugOutput
                ];
            }

            $smtp->quit();

            return [
                "success" => true,
                "message" => Loc::getMessage("DDAPP_TOOLS_SMTP_MESSAGE_CONNECT_OK"),
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