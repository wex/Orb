<?php
/**
 * Orb Bootstrap
 * @author  Copyright &copy; Niko Hujanen, 2011
 * @version 1.00
 */

define('APPLICATION_PATH', (getenv('APPLICATION_PATH') ? getenv('APPLICATION_PATH') : realpath(dirname(__FILE__) . '/../')));
define('APPLICATION_MODE', (in_array(getenv('APPLICATION_MODE'), array('development')) ? getenv('APPLICATION_MODE') : 'production'));

// Set include path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/inc/'),
    realpath(APPLICATION_PATH . '/ext/'),
    realpath(APPLICATION_PATH . '/app/models/'),
    realpath(APPLICATION_PATH . '/app/views/include/'),
    get_include_path()
)));

// Include and run application
require_once 'Application.php';
$orb = Application::run();