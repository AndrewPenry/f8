<?php
namespace F8;

/**
 * Class View
 *
 * Views are the "V" of MVC. Views interpret the Documents and display them. A View should contain ONLY display logic.
 *
 * @package F8
 */
abstract class View {

    /**
     * Renders the output. Should end up echoing or otherwise transmitting data.
     *
     * @param Router $router
     * @param mixed $data
     * @param array $errors
     * @return void
     */
    abstract public function render(Router $router, $data, &$errors);
}