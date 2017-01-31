<?php
namespace F8;

class Locator
{

    private static $_instance;
    private $_services = [];

    // This makes it a Singleton!
    private function __construct() {}
    private function  __clone() {}
    public static function getInstance() {
        if(!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function register(string $name, $service) {
        $this->_services[$name] = $service;
    }

    public function locate(string $name) {
        return $this->_services[$name] ?? null;
    }

}