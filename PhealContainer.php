<?php
class PhealContainer
{
    private $container = array();
    
    public function add_element($key, $val)
    {
        $this->container = array_merge($this->container, array($key=>$val));
    }

    public function  __get($name)
    {
        if(isset($this->container[$name]))
                return $this->container[$name];
        return null;
    }
}