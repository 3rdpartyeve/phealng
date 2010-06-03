<?php
class PhealElement
{
    public $name;
    public $value;
    public $attribs = array();

    protected function  __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function add_attrib($key, $val)
    {
        $this->attribs = array_merge(array($key => $val));
    }

    public function __get($name)
    {
        if(isset($this->attribs[$name]))
            return $this->attribs[$name];
        return $this->value->$name;
    }

    public static function parse_element($element)
    {
        if($element->getName() =="rowset")
        {
                $re = new PhealRowSet($element);
        } else {
            $key = $element->getName();
            if(count($element->children()) > 0)
            {
                $container = new PhealContainer();
                foreach($element->children() as $celement)
                {
                    $cel = PhealElement::parse_element($celement);
                    if(count($celement->attributes()) > 0)
                        $container->add_element($cel->name, $cel);
                    else
                        $container->add_element($cel->name, $cel->value);
                }
                $value = $container;
            } else $value = (String) $element;

            $re = new PhealElement($key, $value);
            if(count($element->attributes()) > 0)
            {
                foreach($element->attributes() as $attelem)
                {
                    $re->add_attrib($attelem->getName(), (String) $attelem);
                }
            }

        }
        return $re;
    }
}