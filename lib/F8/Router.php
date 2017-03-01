<?php
namespace F8;

use Psr\Log\LoggerInterface;
use Sluggo\Sluggo;

class Router
{

    public $url;
    public $controller;
    public $uc_controller;
    public $action;
    public $vars = [];
    public $seo;
    public $word_separator = '-';

    public $rawurl;             // The raw url from the request uri (includes query)
    public $rawurlnoquery;      // The raw url with the query string removed.
    public $is_post = false;

    /** @var \Psr\Log\AbstractLogger */
    public $logger;             // A PSR-3 compatible logger (Required)
    public $viewSwitch;         // The ViewSwitch for handling different view types

    /** @var ErrorFactory */
    public $messageFactory;     // The messageFactory for creating new messages (dangers, warnings, successes, infos)
    public $connections = [];   // A db connection, most likely MongoDB (Optional)

    public $debug = 0;          // 0 = none, 1 = critical, 2 = info

    /** @var CurrentUser */
    public $currentUser;        // A representation of the current user

    public $dangers = [];
    public $warnings = [];
    public $successes = [];
    public $infos = [];

    public $isConsole = false;

    protected $appNamespace = 'App';
    protected $_rerouting = false;

    public function __construct(LoggerInterface $logger, ViewSwitch $viewSwitch)
    {
        if (php_sapi_name() == 'cli') {
            $this->isConsole = true;
            global $argv;
        }
        if (!session_id()) session_start();

        $this->logger = $logger;
        $this->viewSwitch = $viewSwitch;
        $this->messageFactory = new ErrorFactory('\F8\Error');

        if ($this->isConsole) {
            if (!empty($argv[1])) {
                $this->rawurl = $argv[1];
            } else {
                $this->rawurl = '/index/console_default';
            }
        } else {
            $this->rawurl = $_SERVER['REQUEST_URI'];
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) $this->is_post = true;
        }
        $this->rawurlnoquery = strpos($this->rawurl, '?') === false ? $this->rawurl : substr($this->rawurl, 0,
            strpos($this->rawurl, '?'));

        if (!empty($_SESSION['f8_statusArray'])) {
            list($this->dangers, $this->warnings, $this->successes, $this->infos) = $_SESSION['f8_statusArray'];
            unset($_SESSION['f8_statusArray']);
        }

        if (!empty($_SESSION['f8_currentUser']) && $_SESSION['f8_currentUser'] instanceof CurrentUser) {
            $this->currentUser = $_SESSION['f8_currentUser'];
        } elseif (class_exists($this->appNamespace . '\\CurrentUser',
                true) && is_subclass_of($this->appNamespace . '\\CurrentUser', 'F8\\CurrentUser')
        ) {
            $cName = $this->appNamespace . '\\CurrentUser';
            $this->currentUser = new $cName();
        } else {
            throw new \LogicException("The CurrentUser class must be provided in the Application Namespace.");
        }

    }

    public function go()
    {
        $this->parseRoute($this->rawurlnoquery)->verifyRoute($errors);
        $data = $this->followRoute($errors);
        if (!$this->_rerouting) $this->viewSwitch->go($this, $data, $errors);
    }

    public function reroute($url)
    {
        $this->parseRoute($url)->verifyRoute($errors);
        $data = $this->followRoute($errors);
        if (!$this->_rerouting) $this->viewSwitch->go($this, $data, $errors);
        $this->_rerouting = true;
        return [];
    }

    public function parseRoute($url)
    {
        $this->url = $url;

        $urlParts = explode('/', $url);

        if ($urlParts[0] == '') array_shift($urlParts); // If the url starts with a / then the first part will be empty.

        // Treat the first two parts differently
        $controllerCandidate = array_shift($urlParts);
        if (!$this->storeVar($controllerCandidate)) {
            $this->controller = $controllerCandidate;
            $actionCandidate = array_shift($urlParts);
            if (!$this->storeVar($actionCandidate)) {
                $this->action = $actionCandidate;
            }
        }

        foreach ($urlParts as $part) {
            if (!$this->storeVar($part)) {
                if ($this->seo) {
                    $this->seo .= '/' . $part;
                } else {
                    $this->seo = $part;
                }
            }
        }

        if (empty($this->controller)) $this->controller = 'index';
        if (empty($this->action)) $this->action = 'view';

        $this->uc_controller = str_replace($this->word_separator, '',
            ucwords($this->controller, $this->word_separator));

        return $this;
    }

    /**
     * If the part is in named variable format (name:value), then add it to $this->vars and return true.
     * Otherwise, return false.
     *
     * @param string $part
     *
     * @return bool
     */
    function storeVar($part)
    {
        if ($pos = strpos($part, ':')) { // This is OK, becasue it must have at least 1 character before the colon.
            $key = substr($part, 0, $pos);
            $value = substr($part, $pos + 1);
            $this->vars[$key] = rawurldecode($value);
            return true;
        } else {
            return false;
        }
    }

    function getVar($var)
    {
        if (isset($this->vars[$var])) {
            return $this->vars[$var];
        } else {
            return null;
        }
    }

    function setVar($var, $value)
    {
        $this->vars[$var] = $value;
    }


    /**
     * @param array $errors
     *
     * @return \F8\Router $this
     */
    public function verifyRoute(& $errors)
    {
        if (!is_array($errors)) $errors = [];

        if (!class_exists($this->appNamespace . '\\Controller\\' . $this->uc_controller, true)) {
            $errors[] = ['code' => '1000', 'message' => sprintf(_('%s not found.'), $this->uc_controller)];
            $this->controller = 'index';
            $this->uc_controller = 'Index';
            $this->action = '_404';
            $this->vars = ['errors' => $errors];
            return $this;
        }
        if (!is_subclass_of($this->appNamespace . '\\Controller\\' . $this->uc_controller, 'F8\\Controller')) {
            $errors[] = ['code' => '1001', 'message' => sprintf(_('%s is not a controller.'), $this->uc_controller)];
            $this->controller = 'index';
            $this->uc_controller = 'Index';
            $this->action = '_404';
            $this->vars = ['errors' => $errors];
            return $this;
        }
        if (!is_callable([$this->appNamespace . '\\Controller\\' . $this->uc_controller, $this->action])) {
            $errors[] = ['code' => '1002', 'message' => sprintf(_('%s action does not exist.'), $this->action)];
            if (is_callable([$this->appNamespace . '\\Controller\\' . $this->uc_controller, '_404'])) {
                // This controller has its own 404 error handling.
                $this->action = '_404';
                $this->vars = ['errors' => $errors];
            } else {
                $this->controller = 'index';
                $this->uc_controller = 'Index';
                $this->action = '_404';
                $this->vars = ['errors' => $errors];
            }
            return $this;
        }
        return $this;
    }

    public function followRoute(
        /** @noinspection PhpUnusedParameterInspection */
        &$errors)
    {
        $cName = $this->appNamespace . '\\Controller\\' . $this->uc_controller;
        $c = new $cName($this);
        $a = $this->action;
        return $c->$a();
    }

    public function makeRelativeURL($controller, $action, array $vars, $seo = "")
    {
        $url = '/' . $controller . '/' . $action;
        foreach ($vars as $key => $value) {
            $url .= '/' . $key . ':' . rawurlencode($value);
        }
        if ($seo) {
            $sluggo = new Sluggo($seo);
            $url .= '/' . $sluggo->getSlug();
        }
        return $url;
    }

    public function makeAbsoluteURL($controller, $action, array $vars, $seo = "")
    {
        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $url .= $this->makeRelativeURL($controller, $action, $vars, $seo);
        return $url;
    }

    public function danger($message, $code = 809000, $extra = [])
    {
        $this->dangers[] = $this->messageFactory->message($message, $code, $extra);
    }

    public function warning($message, $code = 809000, $extra = [])
    {
        $this->warnings[] = $this->messageFactory->message($message, $code, $extra);
    }

    public function success($message, $code = 809000, $extra = [])
    {
        $this->successes[] = $this->messageFactory->message($message, $code, $extra);
    }

    public function info($message, $code = 809000, $extra = [])
    {
        $this->infos[] = $this->messageFactory->message($message, $code, $extra);
    }

    public function addErrorsToDangers($errors)
    {
        foreach ($errors as $error) {
            if ($error instanceof Error) {
                $this->dangers[] = $error;
            }
        }
    }

    public function redirect($url)
    {
        $_SESSION['f8_statusArray'] = [$this->dangers, $this->warnings, $this->successes, $this->infos];
        header('Location: ' . $url);
        exit();
    }

}