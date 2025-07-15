<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use DDAPP\Tools\Main;
use DDAPP\Tools\Helpers\FileHelper;

// Проверяем, что модуль установлен
if (!CModule::IncludeModule(Main::MODULE_ID)) {
    return;
}

Loc::loadMessages(__FILE__);

$typeFilesystem = Option::get(Main::MODULE_ID, "disk_type_filesystem");

if (!$typeFilesystem) {
    return;
}

$wantSpace = Option::get(Main::MODULE_ID, "disk_free_space") * (1024 * 1024);

if ($typeFilesystem == 2) {
    $busyPlace = Option::get(Main::MODULE_ID, "disk_busy_place");
    $totalSpace = Option::get(Main::MODULE_ID, "disk_all_space");
    $freeSpace = $totalSpace - $busyPlace;
} else {
    $totalSpace = disk_total_space($_SERVER["DOCUMENT_ROOT"]);
    $freeSpace = disk_free_space($_SERVER["DOCUMENT_ROOT"]);
    $busyPlace = $totalSpace - $freeSpace;
}
?>

<div class="bx-gadgets-info">
    <div class="bx-gadgets-content-padding-rl">
        <table class="bx-gadgets-info-site-table" cellspacing="0">
            <tbody>
            <tr>
                <td class="bx-gadget-gray"><?= Loc::getMessage("DDAPP_GADGET_DISK_ALL_SPACE") ?>:</td>
                <td><?= FileHelper::formatBytes($totalSpace) ?></td>
            </tr>
            <?php if ($busyPlace) { ?>
                <tr>
                    <td class="bx-gadget-gray"><?= Loc::getMessage("DDAPP_GADGET_DISK_BUSY_SPACE") ?>:</td>
                    <td><?= FileHelper::formatBytes($busyPlace) ?></td>
                </tr>
            <?php } ?>
            <tr>
                <td class="bx-gadget-gray"><?= Loc::getMessage("DDAPP_GADGET_DISK_FREE_SPACE") ?>:</td>
                <td><?= FileHelper::formatBytes($freeSpace) ?></td>
            </tr>
            <?php if ($wantSpace) { ?>
                <tr>
                    <td class="bx-gadget-gray"><?= Loc::getMessage("DDAPP_GADGET_DISK_WANT_SPACE") ?>:</td>
                    <td><?= FileHelper::formatBytes($wantSpace) ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>