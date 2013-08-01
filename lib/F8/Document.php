<?php

namespace F8;
use F8\Router;

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
    public $_fit_errors;

    public function __construct(Router $router){
        $this->_router = $router;
    }

    /**
     * Packs data from an associaitve array source into the document.
     * Does not handle setting object types or anything fancy. That should be implemented by
     * overriding this function.
     *
     * If strict is set to true, the key must match a public property of the Document.
     *
     * @param bool $strict
     * @return $this
     */
    public function fit($array = array(), $strict = false){
        if (empty($array)) {
            if ($this->_router->debug == 2) $this->_router->logger->info(_("Document was empty when attempting a fit"), array("document_type"=>get_class($this)));
            return $this;
        }

        if ($strict) {
            $props = array();
            $reflection = new \ReflectionClass($this);
            $r_props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($r_props as $p) {
                $props[] = $p->getName();
            }
            if ($this->_router->debug == 2) $this->_router->logger->info(_("Using Strict Fit"), array("document_type"=>get_class($this)));
        }

        foreach ($array as $key => $value){
            if ($strict) {
                if (!in_array($key, $props)) {
                    $this->_fit_errors[] = new Error($this->_router, sprintf(_("%s does not exist in Model Document"), $key), 803006, array("document_type"=>get_class($this)) );
                    continue;
                }
            }
            $this->$key = $value;
        }

        return $this;

    }


    /**
     * For finding more than one document
     *
     * @param array $options
     * @param array $errors
     * @return Document[]
     */
    abstract public function search($options, &$errors);

    /**
     * For creating a document
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    abstract public function create($options, &$errors);

    /**
     * For reading one document
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    abstract public function read($options, &$errors);

    /**
     * For updating one document
     * Most commonly, it will be the current document.
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    abstract public function update($options, &$errors);

    /**
     * For deleting one document.
     * Most commonly, it will be the current document that this Document represents.
     * Example:
     * $this->read(array("id"=>7), $errors);
     * $this->delete(array(), $errors);
     * This should probably delete document 7. However, one could implement it so that the id must be passed in the options array.
     *
     * @param array $options
     * @param array $errors
     * @return boolean
     */
    abstract public function delete($options, &$errors);

}