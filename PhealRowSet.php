<?php
class PhealRowSet extends ArrayObject
{
    public $name;
    
    public function __construct($xml)
    {
       $this->name = (String) $xml->attributes()->name;
       foreach($xml->row as $rowxml)
       {
           $row = array();
           foreach($rowxml->attributes() as $attkey => $attval)
           {
               $row[$attkey] = (String) $attval;
           }
           $this->append(new PhealRowSetRow($row));
        }
    }
}

