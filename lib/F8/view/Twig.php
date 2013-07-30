<?php

namespace F8\View;

use F8\Router;

class Twig implements \F8\View {

    protected $loader;
    protected $twig;


    public function __construct($templatePath, $compilePath){
        $this->loader = new \Twig_Loader_Filesystem($templatePath);
        $this->twig = new \Twig_Environment($this->loader, array(
            'cache' => $compilePath,
            'debug' => true,
        ));

    }

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

        $path = $route->controller.'/'.$route->action.'.twig';

        try {
            $template = $this->twig->loadTemplate($path);
            $route->logger->debug("Template $path");
            echo $template->render($data);
            return true;
        } catch (\Exception $e) {
            return false;
        }



    }
}

