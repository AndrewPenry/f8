<?php

namespace F8\View;

use F8\Router;

class JSON extends \F8\View {
    public $pretty = false;

    /**
     * Renders the output. Should end up echoing or otherwise transmitting data.
     *
     * @param Router $router
     * @param mixed $data
     * @param array $errors
     * @return boolean
     */
    public function render(Router $router, $data, &$errors)
    {
        // TODO: Implement render() method.
        if (!$router->isConsole) {
            header('Content-type: application/json');
        }
        if ($this->pretty) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            echo json_encode($data);
        }
        return true;
    }
}