<?php

namespace F8;

/**
 * Class Controller
 *
 * Controllers are the "C" of MVC. Their main purpose is to provide routing endpoints that perform actions on Documents.
 * As such, most of the procedural code of the program is handled by the controllers.
 *
 * Any public method of a Controller should be assumed to be a routing endpoint. The default routing function will parse
 * a URL in order to execute a Controller method. For example:
 *
 * /article/edit
 *
 * will, by default, call the edit method of the {App}\Controller\Article Controller. It will do this be creating a
 * new instance of the Controller and then calling the method. This allows for the use of $this in endpoint methods.
 * Example:
 *
 * public function doSomething () { ... }
 *
 * URLS with no second part will call the "view" method by default. In other words, "/article" is equivilent to
 * "/article/view". The default route for root is "/index/view." This means that create an {App}\Controller\Index with
 * a public view method is a requirement of F8.
 *
 * @package F8
 */
class Controller {

    /** @var \F8\Router */
    protected $_router;

    public function __construct(){
        $this->_router = Locator::getInstance()->router('app');
    }

}