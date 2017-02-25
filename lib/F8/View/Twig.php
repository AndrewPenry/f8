<?php
namespace F8\View;

use F8\Router;
use F8\View;

class Twig extends View
{

    protected $loader;
    protected $twig;


    public function __construct($templatePath, $compilePath)
    {
        $this->loader = new \Twig_Loader_Filesystem($templatePath);
        $this->twig = new \Twig_Environment($this->loader, [
            'cache' => $compilePath,
            'debug' => true,
        ]);

    }

    public function getEnvironment()
    {
        return $this->twig;
    }

    /**
     * Renders the output. Should end up echoing or otherwise transmitting data.
     *
     * @param Router $router
     * @param array  $data
     * @param array  $errors
     *
     * @return boolean
     */
    public function render(Router $router, $data, &$errors)
    {

        $path = $router->controller . '/' . $router->action . '.twig';

        try {
            $template = $this->twig->loadTemplate($path);
            $data['_router'] = $router;
            echo $template->render($data);
            return true;
        } catch (\Twig_Error_Loader $e) {
            $errors[] = $router->messageFactory->message("Twig template not found", 809000,
                ['path' => $path, 'exception' => $e->getMessage()]);
            $router->logger->debug("Twig template not found", ['path' => $path, 'exception' => $e->getMessage()]);
            return false;
        } catch (\Exception $e) {
            $errors[] = $router->messageFactory->message("Twig template rendering failed", 809000,
                ['path' => $path, 'exception' => $e->getMessage()]);
            $router->logger->critical("Twig template rendering failed",
                ['path' => $path, 'exception' => $e->getMessage()]);
            return false;
        }
    }


}

