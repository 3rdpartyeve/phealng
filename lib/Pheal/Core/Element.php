<?php
/*
 MIT License
 Copyright (c) 2010 - 2013 Peter Petermann

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
namespace Pheal\Core;

/**
 * PhealElement holds elements of the EVE API
 */
class Element implements CanConvertToArray
{
    /**
     * Name of the Element
     * @var String
     */
    public $_name;

    /**
     * Value of the Element
     * @var mixed
     */
    public $_value;

    /**
     * container containing information that the EVE API stored in XML attributes
     * @var array
     */
    public $_attribs = array();

    /**
     * create new PhealElement
     * @param string $name
     * @param mixed $value
     */
    protected function __construct($name, $value)
    {
        $this->_name = $name;
        $this->_value = $value;
    }

    /**
     * Add an attribute to the attributes container
     * @param string $key
     * @param string $val
     */
    public function addAttrib($key, $val)
    {
        $this->_attribs = array_merge(array($key => $val), $this->_attribs);
    }

    /**
     * Magic method, will check if name is in attributes, if not pass
     * the request on to what ever is stored in value
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->_attribs[$name])) {
            return $this->_attribs[$name];
        }
        return $this->_value->$name;
    }

    /**
     * returns the Object as associated array
     * @return array
     */
    public function toArray()
    {
        $return = array();
        foreach ($this->_attribs as $key => $value) {
            $return[$key] = $value;
        }

        if ($this->_value instanceof CanConvertToArray) {
            $return = array_merge($return, $this->_value->toArray());
        } else {
            $return[$this->_name] = $this->_value;
        }

        return $return;
    }

    /**
     * parse SimpleXMLElement
     * @param \SimpleXMLElement $element
     * @return mixed
     */
    public static function parseElement($element)
    {
        if ($element->getName() == "rowset") {
            $re = new RowSet($element);
        } elseif ($element->getName() == "result" && $element->member) { // corp/MemberSecurity workaround
            $container = new Container();
            $container->addElement('members', new PhealRowSet($element, 'members', 'member'));
            $re = new Element('result', $container);
        } else {
            $key = $element->getName();
            $echilds = $element->children();
            if (count($echilds) > 0) {
                $container = new Container();
                foreach ($echilds as $celement) {
                    $cel = Element::parseElement($celement);
                    if (count($celement->attributes()) > 0) {
                        $container->addElement($cel->_name, $cel);
                    } else {
                        $container->addElement($cel->_name, $cel->_value);
                    }
                }
                $value = $container;
            } else {
                $value = (String)$element;
            }

            $re = new Element($key, $value);
            /** @var $element \SimpleXMLElement */
            if (count($element->attributes()) > 0) {
                foreach ($element->attributes() as $name => $attelem) {
                    $re->addAttrib($name, (String)$attelem);
                }
            }

        }
        return $re;
    }
}
