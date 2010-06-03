<?php
class PhealRowSetRow extends ArrayObject
{
    public function __get($name)
    {
        return $this[$name];
    }
}
