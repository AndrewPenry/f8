<?php
namespace F8;

class ErrorFactory
{
    private $_class;

    public function __construct($class)
    {
        if (!class_exists($class)) {
            throw new \Exception('Missing Error Class: ' . $class);
        }
        $this->_class = $class;
    }

    public function message($error, $code = 809000, $extra = [])
    {
        $class = $this->_class;
        return new $class($error, $code, $extra);
    }

}