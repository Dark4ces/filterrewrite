<?php

defined("DATALIFEENGINE") or exit;

$modLName = "dle_filter";

include_once ENGINE_DIR . "/classes/plugins.class.php";

spl_autoload_register(function ($class) {
    $prefix  = "LazyDev\\Filter\\";
    $baseDir = __DIR__ . "/class/";
    $len     = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return null;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace("\\", "/", $relativeClass) . ".php";

    if (file_exists($file)) {
        require_once $file;
    }
});

LazyDev\Filter\Data::load();

$langVar   = LazyDev\Filter\Data::receive("lang");
$configVar = LazyDev\Filter\Data::receive("config");
