<?php
namespace F8;

use F8\Service\DBInterface;
use F8\Service\NullDB;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Locator
{

    private static $_instance;
    private $_services = [];

    // This makes it a Singleton!
    private function __construct() {
        $this->_services['db']['null'] = new NullDB();
        $this->_services['logger']['null'] = new NullLogger();

    }
    private function  __clone() {}
    public static function getInstance() {
        if(!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function register(string $type, string $name, $service) {
        $this->_services[$type][$name] = $service;
    }

    public function locate(string $type, string $name) {
        return $this->_services[$type][$name] ?? $this->_services[$type]['null'] ?? null;
    }

    /**
     * @param $name
     * @return LoggerInterface
     */
    public function logger($name): LoggerInterface {
        return $this->_services['logger'][$name] ?? $this->_services['logger']['null'];
    }

    /**
     * @param $name
     * @return DBInterface
     */
    public function db($name): DBInterface {
        return $this->_services['db'][$name] ?? $this->_services['db']['null'];
    }

}