<?php

class User extends Orm
{
    static $table = 'users';
    static $fields = array(
        'state' => array(
            'type'      => 'string',
            'options'   => array('pending', 'active', 'deleted'),
            'default'   => 'pending',
        ),
        'username' => array(
            'type'      => 'string',
            'unique'    => true,
        ),
        'password' => array(
            'type'      => 'string',
        ),
    );
    
    public static function logged()
    {
        if (!isset($_SESSION['orb']['user'])) return false;
        return ($_SESSION['orb']['user']->id > 0);
    }
    
    public static function current()
    {
        if (!isset($_SESSION['orb']['user'])) return false;
        return @$_SESSION['orb']['user'];
    }
    
    public static function login($username, $password)
    {
        $class = get_called_class();
        $user = $class::select()
                     ->where('username = ?', $username)
                     ->where('password = ?', sha1($password))
                     ->where('state = ?', 'active')
                     ->getOne();
        $_SESSION['orb']['user'] = $user;
        
        return ($user && $user->id > 0);
    }
    
    
    public static function logout()
    {
        unset($_SESSION['orb']['user']);
        return true;
    }
    
}