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
 * RowSetRow, extends array object to allow
 * usage as array
 */
class PhealRowSetRow extends ArrayObject implements PhealArrayInterface
{

    /**
    * @var string if the element has a specific string value, 
    * it can be stored here;
    */
    private $_stringValue = null; 

    /**
    * set string value of row
    * @param string $string 
    */ 
    public function setStringValue($string)
    {
        $this->_stringValue = $string;
    }

    /**
    * Magic __toString method, will return stringvalue of row
    */ 
    public function __toString()
    {
        return $this->_stringValue;
    }

    /**
     * magic function to allow access to the array like an object would do too
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this[$name];
    }

    /**
     * returns the Object as associated array
     * @return array
     */
    public function toArray()
    {
        $return = array();
        foreach($this AS $key => $value)
            $return[$key] = ($value instanceof PhealArrayInterface) ? $value->toArray() : $value;
        
        if($this->_stringValue)
            $return['_stringValue'] = $this->_stringValue;
        
        return $return;
    }
}
