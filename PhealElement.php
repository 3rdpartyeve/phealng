<?php
/*
 Copyright (c) 2010 Peter Petermann

 Permission is hereby granted, free of charge, to any person
 obtaining a copy of this software and associated documentation
 files (the "Software"), to deal in the Software without
 restriction, including without limitation the rights to use,
 copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the
 Software is furnished to do so, subject to the following
 conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 OTHER DEALINGS IN THE SOFTWARE.
*/

/**
 * PhealElement holds elements of the EVE API
 */
class PhealElement
{
    /**
     * Name of the Element
     * @var String
     */
    public $name;

    /**
     * Value of the Element
     * @var mixed
     */
    public $value;
    
    /**
     * container containing information that the EVE API stored in XML attributes
     * @var array
     */
    public $attribs = array();

    /**
     * create new PhealElement
     * @param string $name
     * @param mixed $value
     */
    protected function  __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Add an attribute to the attributes container
     * @param string $key
     * @param string $val
     */
    public function add_attrib($key, $val)
    {
        $this->attribs = array_merge(array($key => $val));
    }

    /**
     * Magic method, will check if name is in attributes, if not pass
     * the request on to what ever is stored in value
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if(isset($this->attribs[$name]))
            return $this->attribs[$name];
        return $this->value->$name;
    }

    /**
     * parse SimpleXMLElement
     * @param SimpleXMLElement $element
     * @return mixed
     */
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