<?php

namespace F8;


abstract class ViewSwitch {

    public $views;
    public $default;

    /**
     * @param Router $router
     * @param mixed $data
     * @param array $errors
     * @return void
     */
    abstract function go(Router $router, $data, &$errors);

    public function registerDefaultView( View $view) {

        $this->default = $view;

    }

}