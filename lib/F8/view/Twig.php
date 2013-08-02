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
     * @param Router $router
     * @param array $data
     * @param array $errors
     * @return boolean
     */
    public function render(Router $router, $data, &$errors)
    {

        $path = $router->controller.'/'.$router->action.'.twig';

        try {
            $template = $this->twig->loadTemplate($path);
            $router->logger->debug("Template $path");
            $data['_router'] = $router;
            echo $template->render($data);
            return true;
        } catch (\Exception $e) {
            return false;
        }



    }
}

