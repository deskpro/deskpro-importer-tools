<?php

spl_autoload_register(function($classname) {
    if (strpos($classname, 'DeskPRO\\ImporterTools') !== 0) {
        return false;
    }

    $classname = str_replace('DeskPRO\\ImporterTools', '', $classname);
    $parts     = explode('\\', $classname);
    if (count($parts) === 1) {
        return false;
    }

    $filePath = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
    if ($filePath && file_exists($filePath)) {
        require_once $filePath;
        return true;
    }

    return false;
});
