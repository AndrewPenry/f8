<?php

namespace F8\View;

use F8\Router;

class JSON implements \F8\View {

    /**
     * Renders the output. Should end up echoing or otherwise transmitting data.
     *
     * @param Router $route
     * @param mixed $data
     * @param array $errors
     * @return boolean
     */
    public function render(Router $route, $data, &$errors)
    {
        // TODO: Implement render() method.
        header('Content-type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        return true;
    }
}