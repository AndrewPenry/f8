<?php
namespace F8;

use F8\View\JSON;

/**
 * Class ViewSwitch
 * Each App should have one ViewSwitch. The ViewSwitch chooses the appropriate view based on the paramaters or state.
 * For example, it may choose whether to send HTML or JSON based on whether the URL Query conatins "method=ajax".
 * The default view is pretty-print JSON
 *
 * @package F8
 */
class ViewSwitch
{

    public $views;
    /** @var View */
    protected $_default;

    /**
     * @param Router $router
     * @param mixed  $data
     * @param array  $errors
     *
     * @return void
     */
    public function go(Router $router, $data, &$errors)
    {
        /** @var JSON $view */
        $view = $this->_default;
        $view->render($router, $data, $errors);
    }

    public function __construct()
    {
        $jsonView = new JSON();
        $jsonView->pretty = true;
        $this->registerDefaultView($jsonView);
    }

    public function registerDefaultView(View $view)
    {
        $this->_default = $view;
    }

    public function getDefaultView(): View
    {
        return $this->_default;
    }
}