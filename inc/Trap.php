<?php
/**
 * Orb Exception
 * @author  Copyright &copy; Niko Hujanen, 2011
 * @version ALPHA
 */

class Trap
{
    /**
     * Handle exception
     */
    public static function handle($e)
    {
        if ($e instanceof ErrorException) {
            if (Application::$config->debug->errors)
                return self::show($e);
            
            return self::log($e);
        } else {
            if (Application::$config->debug->exceptions)
                return self::show($e);
            
            return self::log($e);            
        }
    }
    
    public static function show($e)
    {
        echo "<div style='font-family: Courier; font-size: 11px; 
                          font-weight: normal; padding: 10px; 
                          margin: 20px; background: #fdd; color: #c00; 
                          white-space: pre; border: 1px solid #c00; 
                          border-radius: 13px;'>{$e}</div>";
        return true;        
    }
    
    public static function log($e)
    {
        
        return true;        
    }
}