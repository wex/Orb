<?php
/**
 * Orb Application
 * @author  Copyright &copy; Niko Hujanen, 2011
 * @version 1.00
 */

require_once 'Functions.php';

class Application
{
    static $_instance   = false;
    static $db          = false;
    static $config      = false;
    static $base        = false;
    static $uri         = false;

    static $controller = false;
    static $action     = false;

    private $format     = false;

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
        
        if (file_exists(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Orb.php'))
            include APPLICATION_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Orb.php';
        
        if (class_exists('Orb', false)) {
            $orb = new Orb;
        }        
        
        $this->route();        
        $this->configure();
        
        if (!Controller::exists(Application::$controller)) throw new Exception(sprintf('Invalid controller (%s)', Application::$controller));
        $controller = Controller::factory(Application::$controller, $this);

        if (!$controller->callable(Application::$action)) throw new Exception(sprintf('Invalid action (%s)', Application::$action));
        $result = $controller->call(Application::$action);
        if ($result === false) throw new Exception('Not allowed.');

        if ($this->format !== false) {
            switch ($this->format) {
                case 'json':
                    if ($controller::$json != '*' && !in_array('*', $controller::$json) && !in_array($this->action, $controller::$json)) throw new Exception("JSON not allowed.", 401);
                    echo Template_Json::render($result);
                    return $this;
                default:
                    throw new Exception("Unknown format '{$this->format}'");
                    break;
            }
        }

        $this->template = $controller::$layout;
        if (is_array($result) && isset($result['template']))
            $this->template = $result['template'];
        
        if (!Template::exists($this->template)) throw new Exception(sprintf('Invalid template (%s)', $this->template));
        
        $this->view = Application::$controller . '/' . Application::$action;
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
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_format = false;

        if (strstr($request_uri, '?'))
            list($request_uri, $request_params) = explode('?', $_SERVER['REQUEST_URI'], 2);
        
        if (strstr($request_uri, '#'))
            list($request_uri, $request_jump) = explode('#', $request_uri);

        if (strstr($request_uri, '.')) {
            $parts = explode('.', $request_uri);
            $request_uri    = implode('.', array_slice($parts, 0, -1));
            $request_format = strtolower(implode('.', array_slice($parts, -1)));
        }

        $this::$uri = trim($request_uri, '/');
        
        $request = explode('/', substr($request_uri, 1), 3);
        
        $this->format               = (in_array($request_format, array('json'))) ? $request_format : false;
        Application::$controller    = (isset($request[0]) && strlen($request[0])) ? $request[0] : 'index';
        Application::$action        = (isset($request[1]) && strlen($request[1])) ? $request[1] : 'index';
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
    
    public static function message($text = false, $type = 'error')
    {
        if ($text === false && isset($_SESSION['orb']['__message'])) {
            $html = '<div id="orb-message" class="'. $_SESSION['orb']['__message'][1] .'">'. $_SESSION['orb']['__message'][0] .'</div><script>$(function() { $("#orb-message").delay(2000).fadeOut(2000); });</script>';
            unset($_SESSION['orb']['__message']);
            return $html;
        } else if ($text !== false) {
            $_SESSION['orb']['__message'] = array($text, $type);
        }     
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
        include_once str_replace(array('\\', '_'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $name) . '.php';
    }
    
}