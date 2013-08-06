<?php

namespace F8\View;

use F8\Router;

class JSON implements \F8\View {

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
        header('Content-type: application/json');
        echo json_encode($data);
        return true;
    }
}