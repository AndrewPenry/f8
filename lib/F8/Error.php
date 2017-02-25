<?php
namespace F8;

class Error
{
    public $error;
    public $code;
    public $extra;

    public function __construct($error, $code = 809000, $extra = [])
    {
        $this->error = $error;
        $this->code = $code;
        $this->extra = $extra;
    }
}