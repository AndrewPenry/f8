<?php

namespace F8;

/**
 * Class ViewSwitch
 *
 * Each App should have one ViewSwitch. The ViewSwitch chooses the appropriate view based on the paramaters or state.
 * For example, it may choose whether to send HTML or JSON based on whether the URL Query conatins "method=ajax"
 *
 * @package F8
 */
abstract class ViewSwitch {

    public $views;
    public $default;

    /**
     * @param Router $router
     * @param mixed $data
     * @param array $errors
     * @return void
     */
    abstract public function go(Router $router, $data, &$errors);

    public function registerDefaultView( View $view) {
        $this->default = $view;
    }

}