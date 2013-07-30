# Internal Coding Practices

## Dependency injection

Simply put, this is setting dependancy object either in the contructor or using a setter, instead of directly creating the new dependancy object in the object's code. This allows us to be all sorts of clever, by only type matching interfaces or abstract classes, which makes the parts of the program a bit more intechangeable.

For example, `F8\Router` **must have** a logger. We only check to see if it is a PSR-3 Logger, which means you can use Monolog, or Analog, or any other logger you want that implements `Psr\Log\LoggerInterface`.

    :::php startinline
    class Router {

        public $logger;             // A PSR-3 compatable logger

        public function __construct(\Psr\Log\LoggerInterface $logger){
            $this->logger = $logger;
        }
    }

In index.php:

    :::php startinline
    $logger = new Monolog\Logger('default');
    $r = new F8\Router($logger);

Sure, we could have just used Monolog right in the constructor, but this makes the framework much more flexible. As more PSR or other common interfaces get adopted (such as Slugify, which will let us not lock people into Sluggo).

For optional dependancies, use setter injection. Hypothetical example:

    :::php startinline
    $logger = new Monolog\Logger('default');
    $r = new F8\Router($logger);

    $cache = new Some\Sort\Of\Cache();
    $r->setCache($cache);


## Errors and Exceptions

There are basically 3 ways to show an error.

First, by using `trigger_error()`. This generates a PHP error, which will write to the error log, and may stop execution of the script. F8 does not use a custom error_handler, so this will not be handled in any special way. There is almost never a reason to do this. **Do not use trigger_error.**

Second, by throwing an Exception. Exceptions mean that something exceptional happened. In other words, something you did not anticipate happened, and you don't know how to handle it, but you probably want the code to still going. Exceptions, especially those derived from the SPL LogicException, should always lead to a fix in the code. One example is a string that is longer than the maximum allowed. In this case, there is probably an error in a widget, and it should be fixed. F8 will have a set of Exceptions that extended the SPL Exception classes, adding automatic exception logging. **Only use an Exception to catch things that signal that the program may need to be fixed or that there is a security breach.** Note that this is a departure from our old F7 habit of using exceptions for validation functions. Those should be handled as explained below.

The final way is to set an error code and then check for its existence. Here is an example:

    :::php startinline
    function validate_int($value, &$error){
        $error = false;
        if (preg_match('|\D|',$value) {
            $error = 808003;
        }
        return $value;
    }

    $age = validate_int($_GET['age'], $error);
    if ($error) {
        // Age was not an int
    }

$error is being set by reference to an error code, or false if no error exists. The the main code is checking to see if there is a code. **This is the correct way to handle validating user input**, as it is expected that the user may enter correct form information. Incorrect form information probably does not to be logged. If it does, the main code itself can throw an Exception, so that it does get logged. For example, if you are validating data that comes from an ajax or flash widget that has no user input, failing this validation means an error in the widget or that a user is attempting to alter the data sent. In that case, you would want an exception, to either fix the code or ban the user. For example:

    :::php startinline
    function validate_user_token($userid, $token, &$error){
        $error = false;
        if (md5($userid.'mysalt') !== $token) {
            $error = 900111;
        }
        return $userid;
    }

    $userid = validate_user_token($_GET['userid'], $_GET['token'], $error);
    if ($error) {
         // Token didn't match rehash. Maybe the Flash App is broken.
         throw new App\Exception\Security($error);
    }

Or, alternately, you can use the Logger to write a log, if this kind of violation does not waren't a change of code. Remember, a thrown exception means that you may need to fix, or at least double-check some code.

    :::php startinline
    $userid = validate_user_token($_GET['userid'], $_GET['token'], $error);
    if ($error) {
         // Token didn't match rehash. Hack attempt.
         $router->logger->critical("Possible Hack Attempt", array("get"=>$_GET, "currentUser"=>$user->id));
    }

In addition to not being logged unless you want them to be, this kind of error checking is faster than throwing an exception. In simple tests, I found it to be 6 times slower to throw an exception than to set an error code and check it after execution.
