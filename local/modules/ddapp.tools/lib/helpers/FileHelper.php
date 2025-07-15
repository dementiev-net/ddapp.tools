<?php

namespace DDAPP\Tools\Helpers;

class FileHelper
{
    /**
     * Конвертирует размер файла в человекочитаемый формат
     * @param int $bytes
     * @return string
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes == 0) {
            return "0 B";
        }
        $units = ["B", "KB", "MB", "GB"];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), 2) . " " . $units[$power];
    }

    /**
     * Получает информацию о файле по ID
     * @param int $fileId
     * @return array|null
     */
    public static function getFileInfo(int $fileId): ?array
    {
        $file = \CFile::GetFileArray($fileId);

        if (!$file) {
            return null;
        }

        return [
            "id" => $file["ID"],
            "name" => $file["ORIGINAL_NAME"],
            "size" => self::formatFileSize($file["FILE_SIZE"]),
            "url" => \CFile::GetPath($fileId),
            "extension" => pathinfo($file["ORIGINAL_NAME"], PATHINFO_EXTENSION)
        ];
    }

    /**
     * Проверяет допустимость расширения файла
     * @param string $filename
     * @param array $allowedExtensions
     * @return bool
     */
    public static function isAllowedExtension(string $filename, array $allowedExtensions): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, array_map("strtolower", $allowedExtensions));
    }
}