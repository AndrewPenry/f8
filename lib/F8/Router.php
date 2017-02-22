<?php

namespace F8;

use Sluggo\Sluggo;

abstract class Router {

    public $url;
    public $controller;
    public $uc_controller;
    public $action;
    public $vars = array();
    public $seo;
    public $word_separator = '-';

    public $rawurl;				// The raw url from the request uri (includes query)
    public $rawurlnoquery;		// The raw url with the query string removed.
    public $is_post = false;

    /** @var \Psr\Log\AbstractLogger */
    public $logger;             // A PSR-3 compatible logger (Required)
    public $viewSwitch;         // The ViewSwitch for handling different view types

    /** @var \F8\ErrorFactory */
    public $messageFactory;     // The messageFactory for creating new messages (dangers, warnings, successes, infos)
    public $connections = [];         // A db connection, most likely MongoDB (Optional)

    public $debug = 0;          // 0 = none, 1 = critical, 2 = info

    /** @var \F8\CurrentUser */
    public $currentUser;        // A representation of the current user

    public $dangers = [];
    public $warnings = [];
    public $successes = [];
    public $infos = [];

    public $isConsole = false;

    protected $appNamespace = 'App';
    protected $_rerouting = false;

    public function __construct(\Psr\Log\LoggerInterface $logger, \F8\ViewSwitch $viewSwitch){
        if (php_sapi_name() == 'cli') {
            $this->isConsole = true;
            global $argv;
        }
        if (!session_id()) session_start();

        $this->logger = $logger;
        $this->viewSwitch = $viewSwitch;
        $this->messageFactory = new \F8\ErrorFactory('\F8\Error');

        if ($this->isConsole) {
            if(!empty($argv[1])) {
                $this->rawurl = $argv[1];
            } else {
                $this->rawurl = '/index/console_default';
            }
        } else {
            $this->rawurl = $_SERVER['REQUEST_URI'];
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) $this->is_post = true;
        }
        $this->rawurlnoquery = strpos($this->rawurl, '?') === false ? $this->rawurl : substr($this->rawurl, 0, strpos($this->rawurl, '?'));

        if (!empty($_SESSION['f8_statusArray'])) {
            list($this->dangers, $this->warnings, $this->successes, $this->infos) = $_SESSION['f8_statusArray'];
            unset($_SESSION['f8_statusArray']);
        }

        if (!empty($_SESSION['f8_currentUser']) && $_SESSION['f8_currentUser'] instanceof \F8\CurrentUser) {
            $this->currentUser = $_SESSION['f8_currentUser'];
        }
        elseif (class_exists($this->appNamespace.'\\CurrentUser', true) && is_subclass_of($this->appNamespace.'\\CurrentUser', 'F8\\CurrentUser') ) {
            $cName = $this->appNamespace.'\\CurrentUser';
            $this->currentUser = new $cName();
        } else {
            throw new \LogicException("The CurrentUser class must be provided in the Application Namespace.");
        }

    }

    public function go() {
        $this->parseRoute($this->rawurlnoquery)->verifyRoute($errors);
        $data = $this->followRoute($errors);
        if (!$this->_rerouting) $this->viewSwitch->go($this, $data, $errors);
    }

    public function reroute($url){
        $this->parseRoute($url)->verifyRoute($errors);
        $data = $this->followRoute($errors);
        if (!$this->_rerouting) $this->viewSwitch->go($this, $data, $errors);
        $this->_rerouting = true;
        return [];
    }

    public function parseRoute($url) {
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
                    $this->seo .= '/'.$part;
                } else {
                    $this->seo = $part;
                }
            }
        }

        if (empty($this->controller)) $this->controller = 'index';
        if (empty($this->action)) $this->action = 'view';

        $this->uc_controller = str_replace($this->word_separator, '', ucwords($this->controller, $this->word_separator));

        return $this;
    }

    /**
     * If the part is in named variable format (name:value), then add it to $this->vars and return true.
     * Otherwise, return false.
     *
     * @param string $part
     * @return bool
     */
    function storeVar($part){
        if ($pos = strpos($part, ':')) { // This is OK, becasue it must have at least 1 character before the colon.
            $key = substr($part, 0, $pos);
            $value = substr($part, $pos + 1);
            $this->vars[$key] = rawurldecode($value);
            return true;
        } else {
            return false;
        }
    }

    function getVar($var){
        if (isset($this->vars[$var])) {
            return $this->vars[$var];
        } else {
            return null;
        }
    }

    function setVar($var, $value) {
        $this->vars[$var] = $value;
    }


    /**
     * @param array $errors
     * @return \F8\Router $this
     */
    public function verifyRoute(& $errors){
        if (!is_array($errors)) $errors = array();

        if (!class_exists($this->appNamespace.'\\Controller\\'.$this->uc_controller, true)) {
            $errors[] = array('code'=>'1000', 'message'=>sprintf(_('%s not found.'), $this->uc_controller));
            $this->controller = 'index';
            $this->uc_controller = 'Index';
            $this->action = '_404';
            $this->vars = array('errors' => $errors);
            return $this;
        }
        if (!is_subclass_of($this->appNamespace.'\\Controller\\'.$this->uc_controller, 'F8\\Controller')) {
            $errors[] = array('code'=>'1001', 'message'=>sprintf(_('%s is not a controller.'), $this->uc_controller));
            $this->controller = 'index';
            $this->uc_controller = 'Index';
            $this->action = '_404';
            $this->vars = array('errors' => $errors);
            return $this;
        }
        if (!is_callable(array($this->appNamespace.'\\Controller\\'.$this->uc_controller, $this->action))) {
            $errors[] = array('code'=>'1002', 'message'=>sprintf(_('%s action does not exist.'), $this->action));
            if (is_callable(array($this->appNamespace.'\\Controller\\'.$this->uc_controller, '_404'))) {
                // This controller has its own 404 error handling.
                $this->action = '_404';
                $this->vars = array('errors' => $errors);
            } else {
                $this->controller = 'index';
                $this->uc_controller = 'Index';
                $this->action = '_404';
                $this->vars = array('errors' => $errors);
            }
            return $this;
        }
        return $this;
    }

    public function followRoute(& $errors){
        $cName = $this->appNamespace.'\\Controller\\'.$this->uc_controller;
        $c = new $cName($this);
        $a = $this->action;
        return $c->$a($this);
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

    public function makeAbsoluteURL($controller, $action, array $vars, $seo = "") {
        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'];
        $url .= $this->makeRelativeURL($controller, $action, $vars, $seo);
        return $url;
    }

    /**
     * This converts only public methods of an object into an associative array. It does so recursively and is suitable
     * to prepare objects for insertion into MongoDB. (MongoDB will throw an error if objects have private or protected
     * properties.) It will also destroy values with empty keys, a needed step for MongoDB. If you do not wish to destroy
     * the empty keys, then you will need to override this function in your router.
     *
     * @param \stdClass $object
     * @return array
     */
    public function objectToArray( $object , $filterNull = false) {
        if( !is_object( $object ) && !is_array( $object ) ) {
            return $object;
        }
        if( is_object( $object ) ) {
            /** @var \stdClass $object */
            if (strpos(get_class($object), 'Mongo') === 0) return $object;
            $object = get_object_vars($object);
        }
        /** @var array $object */
        $a =  array_map( array( $this, 'objectToArray'), $object );
        $this->array_destroy_empty_key_recursive($a);

        if ($filterNull) {
            return $this->array_filter_null_recursive($a);
        } else {
            return $a;
        }
    }

    public function array_filter_null_recursive($input)
    {
        foreach ($input as &$value)
        {
            if (is_array($value))
            {
                $value = $this->array_filter_null_recursive($value);
            }
        }
        return array_filter($input, function($v) {return !is_null($v);});
    }

    public function array_destroy_empty_key_recursive(&$input)
    {
        foreach ($input as $key => &$value)
        {
            if ($key === "") {
                unset ($input[$key]);
            }
            if (is_array($value))
            {
                $this->array_destroy_empty_key_recursive($value);
            }
        }
        return;
    }


    public function danger($message, $code = 809000, $extra = array()){
        $this->dangers[] = $this->messageFactory->message($message, $code, $extra);
    }
    public function warning($message, $code = 809000, $extra = array()){
        $this->warnings[] = $this->messageFactory->message($message, $code, $extra);
    }
    public function success($message, $code = 809000, $extra = array()){
        $this->successes[] = $this->messageFactory->message($message, $code, $extra);
    }
    public function info($message, $code = 809000, $extra = array()){
        $this->infos[] = $this->messageFactory->message($message, $code, $extra);
    }

    public function addErrorsToDangers($errors) {
        foreach ($errors as $error) {
            if ($error instanceof \F8\Error) {
                $this->dangers[] = $error;
            }
        }
    }

    public function redirect($url){
        $_SESSION['f8_statusArray'] = [$this->dangers, $this->warnings, $this->successes, $this->infos];
        header('Location: ' . $url);
        exit();
    }
	
	public function sessionOrNewDocument($key, $class) {
	
		if (isset($_SESSION[$key]) && is_object($_SESSION[$key]) && $_SESSION[$key] instanceof $class) {
			return $_SESSION[$key];
		} else {
			$_SESSION[$key] = new $class($this);
			return $_SESSION[$key];
		}
	
	}


}