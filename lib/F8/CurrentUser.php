<?php
namespace F8;

/**
 * Class CurrentUser
 *
 * The CurrentUser class represents the current person using the site, program, or resource. It DOES NOT describe
 * people that are not currently using the site. In many cases, you may retrieve a generic "user" Document from a DB,
 * Facebook, OAuth, etc., and then associate that Document with the F8\CurrentUser when the User logs in.
 *
 * This class allows for some convenience when writing other classes by providing a minimal set of functions that can
 * be overridden in a sensible fashion.
 *
 * @package F8
 */
abstract class CurrentUser {

    public $loggedIn = false;
    /**
     * @var Document
     */
    public $document;

    public function logIn(Router $router, $data, &$errors) {
        $this->loggedIn = true;
        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['f8_currentUser'] = $this;
        }
        return $this->loggedIn;
    }

    public function logOut(Router $router, $data, &$errors) {
        $this->loggedIn = false;
        if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['f8_currentUser'])) {
            unset($_SESSION['f8_currentUser']);
        }
        return $this->loggedIn;
    }

    public function associateDocument(Document $document) {
        $this->document = $document;
        return $document;
    }

} 