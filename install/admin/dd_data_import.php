<?php
// Если в bitrix
if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/dd.tools/")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/dd.tools/admin/dd_data_import.php");
}

// Если в local
if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/local/modules/dd.tools/")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/local/modules/dd.tools/admin/dd_data_import.php");
}