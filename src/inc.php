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
# Normalize env
########################################################################################################################

ini_set('display_errors', "1");
ini_set('memory_limit', -1);
set_time_limit(0);
setlocale(LC_CTYPE, 'C');
date_default_timezone_set('UTC');
ini_set('default_charset', 'UTF-8');
error_reporting(E_ALL);

########################################################################################################################
# Checks
########################################################################################################################

if (!extension_loaded('mbstring')) {
    echo "This tool requires PHP with the mbstring extension:\n";
    echo "http://php.net/manual/en/mbstring.installation.php\n\n";

    echo "Install and enable this extension, then try again.\n";
    exit(1);
}

########################################################################################################################
# Load custom config
########################################################################################################################

if (file_exists(TOOL_ROOT.'/custom-config.php')) {
    require TOOL_ROOT.'/custom-config.php';
}

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

########################################################################################################################
# Libs
########################################################################################################################

function clean_text($string)
{
    global $CONFIG;

    if (!is_string($string) || ctype_digit($string)) {
        return $string;
    }

    $string = trim($string);
    if (!$string) {
        return $string;
    }

    $text_charset = strtoupper(@$CONFIG['text_charset'] ?: 'UTF-8');

    $new_string = mb_convert_encoding($string, 'UTF-8', $text_charset);
    if ($new_string) {
        $string = $new_string;
    } else {
        $new_string = iconv("UTF-8", "UTF-8//IGNORE", $string);
        if ($new_string) {
            $string = $new_string;
        } else {
            echo "Error trying to parse string as UTF-8:\n----------\n\n" . $string;
            echo "\n\n----------\n";
            echo "Make sure you have specified the correct text_charset config option.\n";
            exit(1);
        }
    }

    $string = trim($string);

    return $string;
}