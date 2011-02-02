<?php
/*
 MIT License
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
 * Container Class
 * all elements in the container should be available by PhealContainer->keyname
 */
class PhealContainer implements PhealArrayInterface
{
    /**
     * @var array
     */
    private $_container = array();

    /**
     * Adds an Element to the container
     * @param string $key
     * @param mixed $val
     */
    public function add_element($key, $val)
    {
        $this->_container = array_merge($this->_container, array((String) $key => $val));
    }

    /**
     * magic method for returning values from container on attribute calls
     * @param string $name
     * @return mixed
     */
    public function  __get($name)
    {
        if(isset($this->_container[$name]))
                return $this->_container[$name];
        return null;
    }

    /**
     * returns the Object as associated array
     * @return array
     */
    public function toArray()
    {
        $return = array();
        foreach($this->_container AS $key => $value)
            $return[$key] = ($value instanceof PhealArrayInterface) ? $value->toArray() : $value;

        return $return;
    }
}
