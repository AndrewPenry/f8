<?php
namespace F8;

use F8\Service\DBInterface;
use F8\Service\NullDB;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Locator
{

    protected static $_instance;
    protected $_services = [];

    // This makes it a Singleton!
    protected function __construct()
    {
        $this->_services['db']['null'] = new NullDB();
        $this->_services['logger']['null'] = new NullLogger();

    }

    private function __clone() { }


    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function register(string $type, string $name, $service)
    {
        $this->_services[$type][$name] = $service;
    }

    public function locate(string $type, string $name)
    {
        return $this->_services[$type][$name] ?? $this->_services[$type]['default'] ?? $this->_services[$type]['null'] ?? null;
    }


    public function register_logger(string $name, LoggerInterface $service) {
        $this->register('logger', $name, $service);
    }

    /**
     * @param $name
     *
     * @return LoggerInterface
     */
    public function logger(string $name): LoggerInterface
    {
        return $this->locate('logger', $name);
    }

    /**
     * @param string $name
     * @param DBInterface $service
     */
    public function register_db(string $name, DBInterface $service)
    {
        $this->register('db', $name, $service);
    }

    /**
     * @param $name
     *
     * @return DBInterface
     */
    public function db(string $name): DBInterface
    {
        return $this->locate('db', $name);
    }

    /**
     * @param Router $service
     */
    public function register_router(Router $service)
    {
        $this->register('router', 'default', $service);
    }

    /**
     * @return Router
     */
    public function router()
    {
        return $this->locate('router', 'default');
    }



}