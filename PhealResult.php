<?php
class PhealResult extends PhealResultBase
{
    private $element = null;

    public function __construct($xml)
    {
        $this->request_time = (string) $xml->currentTime;
        $this->cached_until = (string) $xml->cachedUntil;   
        $this->element = PhealElement::parse_element($xml->result);
        
    }

    public function  __get($name)
    {
        return $this->element->$name;
    }
}