<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule("dd.tools")) {
    return;
}

use DD\Tools\Helpers\FileHelper;

$typeFilesystem = COption::GetOptionString("dd.tools", "disk_type_filesystem");

if (!$typeFilesystem) {
    return;
}

$wantSpace = COption::GetOptionString("dd.tools", "disk_free_space") * (1024 * 1024);

if ($typeFilesystem == 2) {
    $busyPlace = COption::GetOptionString("dd.tools", "disk_busy_place", 0);
    $totalSpace = COption::GetOptionString("dd.tools", "disk_all_space");
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
                <td class="bx-gadget-gray"><?= GetMessage("DD_TOOLS_ALL_SPACE") ?>:</td>
                <td><?= FileHelper::formatBytes($totalSpace) ?></td>
            </tr>
            <?php if ($busyPlace) { ?>
                <tr>
                    <td class="bx-gadget-gray"><?= GetMessage("DD_TOOLS_BUSY_SPACE") ?>:</td>
                    <td><?= FileHelper::formatBytes($busyPlace) ?></td>
                </tr>
            <?php } ?>
            <tr>
                <td class="bx-gadget-gray"><?= GetMessage("DD_TOOLS_FREE_SPACE") ?>:</td>
                <td><?= FileHelper::formatBytes($freeSpace) ?></td>
            </tr>
            <?php if ($wantSpace) { ?>
                <tr>
                    <td class="bx-gadget-gray"><?= GetMessage("DD_TOOLS_WANT_SPACE") ?>:</td>
                    <td><?= FileHelper::formatBytes($wantSpace) ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>