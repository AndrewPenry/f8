<?php

namespace F8;

use Sluggo\Sluggo;

class Router {

    public $url;
    public $controller;
    public $action;
    public $vars = array();
    public $seo;

    public $rawurl;				// The raw url from the request uri (includes query)
    public $rawurlnoquery;		// The raw url with the query string removed.
    public $logger;             // A PSR-3 compatable logger

    public function __construct(\Psr\Log\LoggerInterface $logger){
        $this->logger = $logger;

        $this->rawurl = $_SERVER['REQUEST_URI'];
        $this->rawurlnoquery = strpos($this->rawurl, '?') === false ? $this->rawurl : substr($this->rawurl, 0, strpos($this->rawurl, '?'));
    }


    public function go() {
        $this->parseRoute($this->rawurlnoquery)->verifyRoute($errors)->followRoute($errors);
    }

    public function parseRoute($url) {
        $this->url = $url;

        $urlParts = explode('/', $url);

        if ($urlParts[0] == '') array_shift($urlParts); // If the url starts with a / then the first part will be empty.

        $this->controller = array_shift($urlParts);
        $this->action = array_shift($urlParts);

        foreach ($urlParts as $part) {
            if ($pos = strpos($part, ':')) { // This is OK, becasue it must have at least 1 character before the colon.
                $key = substr($part, 0, $pos);
                $value = substr($part, $pos + 1);
                $this->vars[$key] = rawurldecode($value);
            } else {
                if ($this->seo) {
                    $this->seo .= '/'.$part;
                } else {
                    $this->seo = $part;
                }
            }
        }

        if (empty($this->controller)) $this->controller = 'index';
        if (empty($this->action)) $this->action = 'view';

        return $this;
    }

    public function verifyRoute(& $errors){
        if (!is_array($errors)) $errors = array();

        if (!class_exists('App\\Controller\\'.$this->controller, true)) {
            $errors[] = array('code'=>'1000', 'message'=>sprintf(\_('%s not found.'), $this->controller));
            $this->controller = '404';
            $this->action = 'view';
            $this->vars = array('errors' => $errors);
            return $this;
        }
        if (!in_array('F8\\Controller', class_implements('App\\Controller\\'.$this->controller, true))) {
            $errors[] = array('code'=>'1001', 'message'=>sprintf(\_('%s is not a controller.'), $this->controller));
            $this->controller = '404';
            $this->action = 'view';
            $this->vars = array('errors' => $errors);
            return $this;
        }
        if (!is_callable(array('App\\Controller\\'.$this->controller, $this->action))) {
            $errors[] = array('code'=>'1002', 'message'=>sprintf(\_('%s action does not exist.'), $this->action));
            $this->controller = '404';
            $this->action = 'view';
            $this->vars = array('errors' => $errors);
            return $this;
        }

        return $this;
    }

    public function followRoute(& $error){

        $cName = 'App\\Controller\\'.$this->controller;
        $c = new $cName();
        $a = $this->action;
        $c->$a($this);

    }



    public function makeRelativeURL($controller, $action, array $vars, $seo = "") {

        $url = '/'.$controller.'/'.$action;

        foreach($vars as $key=>$value) {
            $url .= '/'.$key.':'.rawurlencode($value);
        }

        if ($seo) {
            $sluggo = new Sluggo($seo);
            $url .= '/'.$sluggo->getSlug();
        }

        return $url;
    }


}