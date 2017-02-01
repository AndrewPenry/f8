<?php

namespace F8;

/**
 * Class Document
 *
 * Documents are the "model" of MVC, but also the "Presenter Data".
 *
 * Documents have low-level storage and retrieval functions.
 * Controllers manipulate the Documents.
 * Views interpret the Documents and display them.
 *
 * Document was chosen over model as a descriptor, because F8 closely follows the NoSQL way of doing things.
 * In most cases, the implementations of these functions will do little more than wrap native MongoDB CRUD functions.
 *
 * @package F8
 */
abstract class Document {

    protected $_router;

    public function __construct(){
        $this->_router = Locator::getInstance()->locate('router', 'app');
    }

    /**
     * For finding more than one document
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param int $count Some trait implementations will be able to return total count by setting this variable
     * @param mixed $db
     * @return Document[]
     */
    abstract public function search(array $options = [], &$errors = [], &$count = null, $db = null);

    /**
     * For creating a document
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param mixed $db
     * @return Document
     */
    abstract public function create(array $options = [], &$errors = [], $db = null);

    /**
     * For reading one document
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param mixed $db
     * @return Document
     */
    abstract public function read(array $options = [], &$errors = [], $db = null);

    /**
     * For updating one document
     * Most commonly, it will be the current document.
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param mixed $db
     * @return Document
     */
    abstract public function update(array $options = [], &$errors = [], $db = null);

    /**
     * For deleting one document.
     * Most commonly, it will be the current document that this Document represents.
     * Example:
     * $this->read(array("id"=>7), $errors);
     * $this->delete(array(), $errors);
     * This should probably delete document 7. However, one could implement it so that the id must be passed in the options array.
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param mixed $db
     * @return boolean
     */
    abstract public function delete(array $options = [], &$errors = [], $db = null);


    /**
     * For saving one document.
     * Most commonly, it will be the current document.
     * Saving is a shortcut for create if new, otherwise update.
     * A RDBMS implantation may do something like INSERT INTO ... IF DUPLICATE KEY UPDATE or REPLACE INTO
     * MongoDB has a save function that checks to see if the _id is set
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param mixed $db
     * @return Document
     */
    abstract public function save(array $options = [], &$errors = [], $db = null);

}