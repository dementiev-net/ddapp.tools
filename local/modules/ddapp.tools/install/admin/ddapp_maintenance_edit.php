<?php
// Если в bitrix
if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/ddapp.tools/")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/ddapp.tools/admin/ddapp_maintenance_edit.php");
}

// Если в local
if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/local/modules/ddapp.tools/")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/local/modules/ddapp.tools/admin/ddapp_maintenance_edit.php");
}