<?php
/**
 * Orb Application
 * @author  Copyright &copy; Niko Hujanen, 2011
 * @version 1.00
 */

class Application
{
    static $_instance   = false;
    static $db          = false;
    static $config      = false;
    static $base        = false;

    private $controller = false;
    private $action     = false;

    private $template   = false;

    /**
     * Singleton __construct
     * @return  <Application>   Instance of Application
     */
    private function __construct()
    {
        spl_autoload_register('Application::autoload');
        set_error_handler('Application::error');
        set_exception_handler('Trap::handle');
        $this->route();        
        $this->configure();
        
        if (!Controller::exists($this->controller)) throw new Exception(sprintf('Invalid controller (%s)', $this->controller));
        $controller = Controller::factory($this->controller, $this);

        if (!$controller->callable($this->action)) throw new Exception(sprintf('Invalid action (%s)', $this->action));
        $result = $controller->call($this->action);
        
        $this->template = $controller::$layout;
        if (is_array($result) && isset($result['template']))
            $this->template = $result['template'];
        
        if (!Template::exists($this->template)) throw new Exception(sprintf('Invalid template (%s)', $this->template));
        
        $this->view = $this->controller . '/' . $this->action;
        if (!View::exists($this->view)) throw new Exception(sprintf('Invalid view (%s)', $this->view));
        
        $html = Template::load($this->template, array('view' => View::load($this->view, $result)));
        echo $html;

        return $this;
    }
    
    /**
     * Configure application, internal
     * @return Always true
     */
    private function configure()
    {
        $file = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config.yaml';
        $this::$config = new Zend_Config_Yaml($file, APPLICATION_MODE);
        $this::$db = Zend_Db::factory($this::$config->db);
        $this::$base = (($_SERVER['SERVER_PORT'] == 445) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . str_replace('index.php', '', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));

        $sessionName = 'orbsession';
        if (isset($this::$config->session->name))
            $sessionName = $this::$config->session->name;
        session_name($sessionName);
        session_start();
        
        return true;       
    }

    /**
     * Reoute request, internal
     * @return Always true
     */
    private function route()
    {
        $request = explode('/', substr($_SERVER['REQUEST_URI'], 1), 3);
        $this->controller   = (isset($request[0]) && strlen($request[0])) ? $request[0] : 'index';
        $this->action       = (isset($request[1]) && strlen($request[1])) ? $request[1] : 'index';
        if (isset($request[2]) && strlen($request[2])) {
            if (substr($request[2], -1) != '/') $request[2] .= '/';
            $params = explode('/', $request[2]);
            for ($i = 0; $i < (floor(substr_count($request[2], '/') / 2) * 2); $i+=2) {
                $_GET[$params[$i]] = $params[$i + 1];
                $_REQUEST[$params[$i]] = $params[$i + 1];
            }
        }
        
        return true;
    }

    /**
     * Singleton run, executes application
     * @return  <Application>   Instance of Application
     */
    public static function run()
    {
        if (self::$_instance === false) self::$_instance = new self();
        return self::$_instance;
    }
    
    public static function redirect($target)
    {
        header('Location: ' . Application::$base . $target);
        exit;
    }
    
    /**
     * Errors to Exceptions
     */
    public static function error($errno, $errstr, $errfile, $errline)
    {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Autoloader
     */
    public static function autoload($name)
    {
        include_once str_replace(array('\\', '_'), array('/', '/'), $name) . '.php';
    }
    
}