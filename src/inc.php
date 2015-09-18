<?php

define('TOOL_ROOT', realpath(__DIR__.'/../'));

########################################################################################################################
# Set up autoloading
########################################################################################################################

spl_autoload_register(function($classname) {
    // Fallback to checking native apps
    $parts = explode('\\', $classname);
    if (count($parts) === 1) {
        return false;
    }

    $file_path = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
    if ($file_path) {
        if (file_exists($file_path)) {
            require_once $file_path;
            return true;
        }
    }

    return false;
});

########################################################################################################################
# Some factories
########################################################################################################################

/**
 * @return \DpTools\ImportWriter\Writer
 */
function create_writer()
{
    $writer = new \DpTools\ImportWriter\Writer(
        TOOL_ROOT.DIRECTORY_SEPARATOR.'data'
    );

    return $writer;
}