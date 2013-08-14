<?php
namespace F8;


class ErrorFactory {
    private $_class;
    private $_router;

    public function __construct(Router $router, $class){
        $this->_router = $router;
        if (!class_exists($class)) {
            throw new \Exception('Missing Error Class: '.$class);
        }
        $this->_class = $class;
    }

    public function message($error, $code = 809000, $extra = array()){
        $class = $this->_class;
        return new $class($this->_router, $error, $code, $extra);
    }

}