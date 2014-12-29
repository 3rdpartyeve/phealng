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
 * Container Class
 * all elements in the container should be available by PhealContainer->keyname
 */
class Container implements CanConvertToArray
{
    /**
     * @var array
     */
    private $myData = array();

    /**
     * Adds an Element to the container
     * @param string $key
     * @param mixed $val
     */
    public function addElement($key, $val)
    {
        $this->myData = array_merge($this->myData, array((String)$key => $val));
    }

    /**
     * magic method for returning values from container on attribute calls
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->myData[$name])) {
            return $this->myData[$name];
        }
        return null;
    }

    /**
     * magic method to implement an interface for isset/empty
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->myData[$name]);
    }

    /**
     * returns the Object as associated array
     * @return array
     */
    public function toArray()
    {
        $return = array();
        foreach ($this->myData as $key => $value) {
            $return[$key] = ($value instanceof CanConvertToArray) ? $value->toArray() : $value;
        }

        return $return;
    }
}
