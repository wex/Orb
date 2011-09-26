<?php
/**
 * Orb Controller Abstract
 * @author  Copyright &copy; Niko Hujanen, 2011
 * @version BETA
 * @abstract
 */

abstract class Controller
{
    /**
     * JSON array
     * * = all
     * @var <array>     Require roles
     */
    static $json = array();

    /**
     * Roles array
     * * = all
     * @ = logged
     * string = group
     * @var <array>     Require roles
     */
    static $roles = array('*' => array('*'));
    
    /**
     * Template, fallback is index.
     * @var <string>    Template name
     */
    static $layout = 'index';
    
    /**
     * Convert given name to ControllerClasses name
     * @param   <string>    $name   Called controller
     * @return  <string>            Name in correct format
     */
    static function toClass($name)
    {
        return ucfirst(strtolower($name)) . 'Controller';
    }

    /**
     * Convert given name to ControllerClasses method
     * @param   <string>    $name   Called action
     * @return  <string>            Name in correct format
     */
    static function toAction($name)
    {
        return strtolower($name) . 'Action';
    }

    /**
     * See if controller exists
     * @param   <string>    $name   Called controller
     * @return  <boolean>           True if exists, otherwise false
     */
    public static function exists($name)
    {
        if (file_exists(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . self::toClass($name) . '.php'))
            return true;

        return false;
    }

    /**
     * Factory a controller by given name
     * @param   <string>        $name   Controller to call
     * @return  <Controller>            Instance of controller
     */
    public static function factory($name)
    {
        $class = self::toClass($name);
        include APPLICATION_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $class . '.php';
        return new $class();
    }

    /**
     * Check if action is callable
     * @param   <string>    $name   Name of action
     * @return  <boolean>           True if possible, otherwise false
     */
    public function callable($name)
    {
        if (method_exists($this, self::toAction($name))) return true;

        return false;
    }

    /**
     * Call controller's action
     * @param   <string>    $name   Name of action
     * @return  <mixed>             Result, usually a array
     */
    public function call($name)
    {
        $access = false;
        if (array_key_exists('*', $this::$roles)) {
            $roles = $this::$roles['*'];
            if (!is_array($roles) && $roles == '*') {
                $access = true;
            } else {
                if (in_array('*', $roles) || in_array(strtolower($name), $roles)) $access = true;
            }
        }        
        if (User::logged() && array_key_exists('@', $this::$roles)) {
            $roles = $this::$roles['@'];
            if (!is_array($roles) && $roles == '*') {
                $access = true;
            } else {
                if (in_array('*', $roles) || in_array(strtolower($name), $roles)) $access = true;
            }
        }
        /**
         * @todo    Implement "rolegroups", not needed yet.
         */
        if ($access === false) {
            if (method_exists('Orb', 'deny')) {
                Orb::deny();
            } else {
                throw new Exception('Access denied', 405);
            }
        }
        
        $method = self::toAction($name);
        return $this->$method();
    }

}