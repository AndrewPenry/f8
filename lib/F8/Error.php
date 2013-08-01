<?php

namespace F8;
use F8\Router;

class Error {
    private $_router;

    public $error;
    public $code;
    public $extra;

    public function __construct(Router $router, $error, $code = 809000, $extra = array()) {
        $this->_router = $router;
        $this->error = $error;
        $this->code = $code;
        $this->extra = $extra;
    }

}