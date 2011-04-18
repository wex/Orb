<?php

class Select extends Zend_Db_Select implements Iterator, Countable
{
    protected $object;
    protected $smt;
    protected $key;
    protected $data;

    public function __construct($className)
    {
        $this->object = $className;
        parent::__construct(Application::$db);
        $this->from(array('t' => $className::$table));
    }

    public function where($q, $v = null, $t = null)
    {
        return parent::where($q, $v, $t);
    }

    public function count()
    {
        $sqlCount = clone $this;
        $sqlCount->reset(Zend_Db_Select::COLUMNS)->columns('COUNT(*)');
        return Application::$db->fetchOne($sqlCount);
    }

    public function getAll()
    {
        $class = $this->object;
        
        $records = array();        
        foreach (Application::$db->fetchAll($this) as $row)
            $records[] = new $class($row, true);

        return $records;
    }

    public function getOne()
    {
        $class = $this->object;
        $data = Application::$db->fetchRow($this);
        if (empty($data)) return null;
        
        return new $class($data, true);
    }

    public function rewind()
    {
        $this->smt = Application::$db->query($this);
        $this->data = $this->smt->fetch();
    }

    public function current()
    {
        $class = $this->object;
        return new $class($this->data, true);
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        $this->data = $this->smt->fetch();
        if (!empty($this->data)) {
            $this->key++;
        } else {
            $this->key = false;
        }
    }

    public function valid()
    {
        if ($this->key === false)
            return false;
        
        return true;
    }
}
