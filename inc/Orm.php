<?php

/**
 * 
 */
abstract class Orm 
{
    static $table = false;
    static $order = 'id ASC';
    static $has_many = array();
    static $fields = array();

    public $errors = false;
    
    protected $data = array();
    protected $dirty = array();
    
    public function __construct($data = array(), $forceClean = false)
    {
        $this->_set('id', null, true);
        foreach ($this::$fields as $key => $field) {
            $this->_set($key, (isset($field['default']) ? $field['default'] : null), $forceClean);
        }

        foreach ($data as $k => $v) {
            $this->_set($k, $v, true);
        }
    }
    
    public function _set($key, $value, $clean = false)
    {
        if ($clean === false)
            $this->dirty[$key] = $key;
        
        return ($this->data[$key] = $value);
    }
    
    public function _get($key)
    {
        if (array_key_exists($key, $this::$has_many)) {
            
            $db = Application::$db;
            $rel = $this::$has_many[$key];
            $object = $rel['model'];
            
            if (array_key_exists('thru', $rel)) {               
                // Many-to-many
                $inherit = array_key_exists('inherit', $rel['thru']) ? $rel['thru']['inherit'] : array();
                $sql = $object::select()
                              ->reset('from')
                              ->reset('columns')
                              ->from(array('m' => $rel['table']), '*')
                              ->join(array('l' => $rel['thru']['table']), 'm.id = l.' . $db->quoteIdentifier($rel['thru']['key']), $inherit)
                              ->where('l.' . $db->quoteIdentifier($rel['key']) . ' = ?', $this->_get('id'));
                
                if (array_key_exists('order', $rel['thru']))
                    $sql->order('l.' . $rel['thru']['order']);
                
                if (array_key_exists('where', $rel['thru'])) {
                    foreach ($rel['thru']['where'] as $where => $value) {
                        $sql->where('l.' . (strstr($where, '?') ? $where : ($where . ' = ?')), $value);
                    }
                }
                
                if (array_key_exists('order', $rel))
                    $sql->order('m.' . $rel['order']);
                
                if (array_key_exists('where', $rel)) {
                    foreach ($rel['where'] as $where => $value) {
                        $sql->where('m.' . (strstr($where, '?') ? $where : ($where . ' = ?')), $value);
                    }
                }
                
                return $sql;
                
            } else {                
                // One-to-many
                $sql = $object::select()
                              ->reset('from')
                              ->reset('columns')
                              ->from(array('m' => $rel['table']), '*')
                              ->where('m.' . $db->quoteIdentifier($rel['key']) . ' = ?', $this->_get('id'));
                
                if (array_key_exists('order', $rel))
                    $sql->order('m.' . $rel['order']);
                
                if (array_key_exists('where', $rel)) {
                    foreach ($rel['where'] as $where => $value) {
                        $sql->where('m.' . (strstr($where, '?') ? $where : ($where . ' = ?')), $value);
                    }
                }
                
                return $sql;
            }
            
        }
        
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        
        return null;
    }
    
    public function __get($key)
    {
        if (array_key_exists($key, $this::$has_many)) {
            if (($this->_get('id') && !array_key_exists($key, $this->data)) || in_array($key, $this->dirty))
                $this->data[$key] = $this->_get($key);
            
            return $this->data[$key];            
        }

        $method = sprintf('get%s', ucfirst(strtolower($key)));
        $value = $this->_get($key);

        if (method_exists($this, $method)) $value = $this->$method($value);
        
        return $value;
    }
    
    public function __set($key, $value)
    {
        if (array_key_exists($key, $this::$has_many)) {

            if (!array_key_exists($key, $this->data))
                $this->data[$key] = array();
            
            $this->dirty[$key] = $key;
            
            return ($this->data[$key][] = $value);
        }
        
        $method = sprintf('set%s', ucfirst(strtolower($key)));
        
        if (method_exists($this, $method)) $value = $this->$method($value);
        
        return $this->_set($key, $value);        
    }
    
    public function __isset($key)
    {
        $value = $this->_get($key);
        return (!empty($value));
    }
    
    /**
     * Validate current models dirty fields.
     * @param   Array   $override   Array of fields to skip
     * @return  Boolean             True if valid, false on invalid.
     */
    public function validate($override = array())
    {
        $fields = $this::$fields;
        $this->errors = array();
        $errors = array();
        
        foreach ($this->dirty as $key => $key) {
            if (in_array($key, $override)) continue;
            
            $error = array();
            
            if (array_key_exists($key, $this::$fields)) {
                // Field
            
                $field = $this::$fields[$key];
                $value = $this->_get($key);

                switch (@$field['type']) {
                    case 'date':
                        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) $error[] = 'Not a date';
                        break;
                    case 'timestamp':
                    case 'datetime':
                        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) $error[] = 'Not a date';
                        break;
                    case 'int':
                    case 'integer':
                    case 'number':
                        if (!is_int($value)) $error[] = 'Not a integer';
                        if (isset($field['min']) && ($value < $field['min'])) $error[] = 'Underflow';
                        if (isset($field['max']) && ($value > $field['max'])) $error[] = 'Overflow';
                        break;
                    case 'decimal':
                    case 'float':
                        if (!is_float($value) && ($value !== 0)) $error[] = 'Not a float';
                        if (isset($field['min']) && ($value < $field['min'])) $error[] = 'Underflow';
                        if (isset($field['max']) && ($value > $field['max'])) $error[] = 'Overflow';
                        break;
                    case 'foreign':
                        if (!empty($value)) {
                            $validator = new Zend_Validate_Db_RecordExists($field['table'], (array_key_exists('key', $field) ? $field['key'] : 'id'), null, Application::$db);
                            if (!$validator->isValid($value)) $error[] = 'Invalid foreign';
                        }
                        break;
                    case 'string':
                    default:
                        if (isset($field['min']) && (strlen($value) < $field['min'])) $error[] = 'Underflow';
                        if (isset($field['max']) && (strlen($value) > $field['max'])) $error[] = 'Overflow';
                        break;
                }

                if ((!isset($field['null']) || (isset($field['null']) && !$field['null'])) && (($value === null) || ($value === false))) $error[] = 'Not null';            
                if (isset($field['options']) && !in_array($value, $field['options'])) $error[] = 'Invalid option';

                if (!$error && isset($field['unique'])) {
                    if (!$this->_get('id')) {
                        $validator = new Zend_Validate_Db_NoRecordExists($this::$table, $key, null, Application::$db);
                        if (!$validator->isValid($value)) $error[] = 'Exists';
                    }
                }
            } else if (array_key_exists($key, $this::$has_many)) {
                // Many-to-many or One-to-many
                if (array_key_exists('thru', $this::$has_many[$key])) {
                    // Many-to-many
                    if (array_key_exists($key, $this->data) && is_array($this->data[$key])) {
                        foreach ($this->data[$key] as $object) {                            
                            if (!$object->validate())
                                $error[] = $object->errors;
                        }
                    }
                } else {
                    // One-to-many
                    if (array_key_exists($key, $this->data) && is_array($this->data[$key])) {
                        foreach ($this->data[$key] as $object) {
                            if (!$object->validate(array($this::$has_many[$key]['key'])))
                                $error[] = $object->errors;
                        }
                    }
                }
            }
            
            if (!empty($error)) {
                $errors[$key] = $error;
            } else {
                unset($this->dirty[$key]);                
            }
            
        }
        
        $this->errors = empty($errors) ? false : $errors;
        
        if (!$this->errors) return true;
        
        return false;
    }
    
    public function save($useTransaction = false)
    {
        if (!empty($this->dirty)) $this->validate();
        if (!empty($errors)) return false;
        
        $db = Application::$db;
        
        if ($useTransaction) 
            $db->beginTransaction();
        
        $data = array();
        $relations = array();
        
        foreach ($this->data as $key => $value) {
            if (array_key_exists($key, $this::$fields)) {
                $data[$key] = $this->_get($key);
            } else if (array_key_exists($key, $this::$has_many) && array_key_exists($key, $this->data) && is_array($this->data[$key])) {
                $relations[$key] = $this->data[$key];
            }            
        }

        try {
            if (!$this->_get('id')) {
                $db->insert($this::$table, $data);
                $this->_set('id', $db->lastInsertId(), true);
            } else {
                $db->update($this::$table, $data, $db->quoteInto('id = ?', $this->_get('id')));            
            }
        } catch (Exception $e) {
            if ($useTransaction)
                $db->rollback();
            
            return false;
        }
        
        foreach ($relations as $key => $relation) {
            $rel = $this::$has_many[$key];
            if (array_key_exists('thru', $rel)) {
                // Many-to-many
                $exists = array(0);
                foreach ($relation as $object) {
                    $data = array(
                        $rel['key']         => $this->_get('id'),
                        $rel['thru']['key'] => $object->_get('id'),
                    );
                    
                    $exists[] = $object->_get('id');
                    if (array_key_exists('inherit', $rel['thru'])) {
                        foreach ($rel['thru']['inherit'] as $inherit) {
                            $data[$inherit] = $object->_get($inherit);
                        }
                    }
                    try {
                        $db->insert($rel['thru']['table'], $data);
                    } catch (Exception $e) { continue; }
                    
                    $db->delete($rel['thru']['table'], implode(' AND ', array(
                        $db->quoteInto($rel['key'] . ' = ?', $this->_get('id')),
                        $db->quoteInto($rel['thru']['key'] . ' NOT IN (?)', $exists),
                    )));
                }
                
            } else {
                // One-to-many
                $exists = array(0);
                foreach ($relation as $object) {
                    $object->_set($rel['key'], $this->_get('id'));
                    $object->save(true);
                    $exists[] = $object->_get('id');
                }
                $db->delete($rel['table'], $db->quoteInto('id NOT IN (?)', $exists));
            }
        }
        
        if ($useTransaction) 
            $db->commit();
        
        return true;
    }
    
    public static function select($array = false)
    {
        $class = get_called_class();
        return new Select($class, $array);
    }
    
    public static function get($id)
    {
        return self::select()->where('id = ?', $id)->getOne();
    }
    
    public static function find($data = array())
    {
        $select = self::select();
        foreach ($data as $k => $v)
            $select->where((strstr($k, '?') ? $k : ($k . ' = ?')), $v);
                
        return $select;
    }
    
    public function destroy()
    {
        $db = Application::$db;
        return $db->delete($this::$table, $db->quoteInto('id = ?', $this->_get('id')));
    }
    
}