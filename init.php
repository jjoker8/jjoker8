<?php
/**
 * Dateiname: init.php
 * Erstellt: 20.09.2016
 *
 * Autor: Joshua Isooba <joshua@sysdesign.de>
 *
 * Beschreibung:
 */
/* try different possible locale names for german */
$loc_de = setlocale(LC_ALL, 'de_DE.UTF-8', 'de_DE@euro', 'de_DE', 'de', 'ge');
#setlocale(LC_ALL, $loc_de);

define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);
define('APP_DIR', '..' . DS . 'app');

define('MONDAY_THIS_WEEK', date("Y-m-d", strtotime('monday this week')));
define('FRIDAY_THIS_WEEK', date("Y-m-d", strtotime('friday this week')));

define('APP_DEV', 0);

if (APP_DEV == true) {
    #error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT . DS . 'logs' . DS . 'app_error_log');
}

const CONTROLLERS = APP_DIR . DS . 'controllers';
const CORE        = APP_DIR . DS . 'core';
const MODELS      = APP_DIR . DS . 'models';
const VIEWS       = APP_DIR . DS . 'views';
const TEMPLATES   = VIEWS . DS . 'templates';

spl_autoload_register(function ($class) {
    require_once (CORE . DS . $class . '.php');
});


if (session_status() == PHP_SESSION_NONE) {
    //session has not started
    session_start();
}


// create the loader object
$loader = new Loader();

// creates the requested controller object based on the 'controller' URL value
$controller = $loader->createController();

/**
 * execute the requested controller's requested method
 * based on the 'action' URL value.
 *
 * P.S. Controller methods output a View.
 */
$controller->executeAction();
