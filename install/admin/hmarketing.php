<?php
define("DD_MODULE_NAMESPACE", "dd.tools");

// Если в bitrix
if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . DD_MODULE_NAMESPACE . "/")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . DD_MODULE_NAMESPACE . "/admin/hmarketing.php");
}

// Если в local
if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . DD_MODULE_NAMESPACE . "/")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . DD_MODULE_NAMESPACE . "/admin/hmarketing.php");
}