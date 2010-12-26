<?php
class SphinxView
{
    public $view = null;

    public function render($file)
    {
        require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/'.$file);
    }
    public function assignObject($object)
    {
        foreach($object as $key => $value){
            $this->assign($key, $value);
        }
    }
    public function assign($key, $value)
    {
        $this->view->{$key} = $value;
    }

    public function __set($name, $value)
    {
        $this->view->$name = $value;
    }
}