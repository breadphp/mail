<?php
namespace Bread\Mail\Mailbox;

class Model
{

    protected $address;

    protected $name;

    public function __construct($address, $name = "")
    {
        $this->address = $address;
        $this->name = $name;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __isset($property)
    {
        return isset($this->$property);
    }

    public function __unset($property)
    {
        unset($this->$property);
    }
}