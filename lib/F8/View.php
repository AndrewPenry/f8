<?php
namespace F8;

interface View {

    /**
     * Renders the output. Should end up echoing or otherwise transmitting data.
     *
     * @param Router $router
     * @param mixed $data
     * @param array $errors
     * @return void
     */
    public function render(Router $router, $data, &$errors);

}