<?php
/**
 * Orb Template
 * @author  Copyright &copy; Niko Hujanen, 2011
 * @version ALPHA
 * @abstract
 */

class Template
{
    private $file = false;
    private $data = array();

    private function __construct($name, $data)
    {
        if (!is_array($data)) $data = array();
        $this->file = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . self::toName($name) . '.php';
        $this->data = $data;

        return $this;
    }

    public static function toName($name)
    {
        return strtolower($name);
    }

    public static function exists($name)
    {
        if (file_exists(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . self::toName($name) . '.php'))
            return true;

        return false;
    }

    public function render()
    {
        extract($this->data);
       
        ob_start();
        include $this->file;
        $html = ob_get_contents();
        ob_end_clean();
        
        return $html;
    }

    public static function load($name, $data = array())
    {
        $template = new self($name, $data);

        return $template->render();
    }
    
}