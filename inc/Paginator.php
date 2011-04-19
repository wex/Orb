<?php

/**
 * Orb Paginator, integrates with Select
 * @uses    Use like Product::select()->paginate()
 * @author  &copy; Niko Hujanen, 2011
 * @version 1.0
 */
class Paginator
{
    public $page = 1;
    public $pages = 1;
    public $count = 0;
    public $limit = 20;
    public $offset = 0;
    public $rows = 0;
    
    protected $var = 'p';
    protected $select = false;
    
    public function __construct(Select &$select, $limit = 20, $var = 'p')
    {
        $this->count    = count($select);
        $this->select   = &$select;
        $this->limit    = ($limit > 0) ? $limit : 20;
        $this->var      = $var;
        
        $this->pages    = ceil($this->count / $this->limit);
        
        if (isset($_REQUEST[$this->var]) && is_numeric($_REQUEST[$this->var]))
            $this->page = $_REQUEST['p'];
        
        if ($this->page < 1) $this->page = 1;
        if ($this->page > $this->pages) $this->page = $this->pages;
        
        $this->offset = ($this->page - 1) * $this->limit;
        $this->rows = ($this->count < ($this->offset + $this->limit)) ? $this->limit : ($this->count % $this->limit);
        
        $select->limit($this->limit, $this->offset);
        
        return $this;
    }
    
    protected function uriTo($num)
    {
        return Application::$base . Application::$uri . '?' . $this->var . '=' . $num;
    }
    
    public function __toString()
    {
        $_min = $this->page - 3;
        $_max = $this->page + 3;
        if ($_min < 1) $_min = 1;
        if ($_max > $this->pages) $_max = $this->pages;

        $html = '<ul class="orb-paginator">';
        $html .= sprintf('<li class="%s%s"><a%s>%s</a></li>',
            'orb-paginator-page-previous',
            (($this->page - 1) < 1) ? ' orb-paginator-page-disabled' : '',
            (($this->page - 1) < 1) ? '' : (' href="' . $this->uriTo($this->page - 1) . '"'),
            '&laquo; Edellinen'
        );

        if ($_min > 1) {
            $html .= sprintf('<li class="%s%s"><a href="%s">%s</a></li>',
                'orb-paginator-page',
                (1 == $this->page) ? ' orb-paginator-page-current' : '',
                $this->uriTo(1),
                1
            );            
        }

        for ($p = $_min; $p <= $_max; $p++) {
            $html .= sprintf('<li class="%s%s"><a href="%s">%s</a></li>',
                'orb-paginator-page',
                ($p == $this->page) ? ' orb-paginator-page-current' : '',
                $this->uriTo($p),
                $p
            );            
        }

        if ($_max < $this->pages) {
            $html .= sprintf('<li class="%s%s"><a href="%s">%s</a></li>',
                'orb-paginator-page',
                ($this->pages == $this->page) ? ' orb-paginator-page-current' : '',
                $this->uriTo($this->pages),
                $this->pages
            );            
        }

        $html .= sprintf('<li class="%s%s"><a%s>%s</a></li>',
            'orb-paginator-page-next',
            (($this->page + 1) > ($this->pages)) ? ' orb-paginator-page-disabled' : '',
            (($this->page + 1) > ($this->pages)) ? '' : (' href="' . $this->uriTo($this->page + 1) . '"'),
            'Seuraava &raquo;'
        );        
        $html .= '</ul>';

        return $html;
    }
    
}