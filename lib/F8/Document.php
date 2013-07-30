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
interface Document {

    /**
     * For finding more than one document
     *
     * @param array $options
     * @param array $errors
     * @return Document[]
     */
    public function search($options, &$errors);

    /**
     * For creating a document
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    public function create($options, &$errors);

    /**
     * For reading one document
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    public function read($options, &$errors);

    /**
     * For updating one document
     * Most commonly, it will be the current document.
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    public function update($options, &$errors);

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
    public function delete($options, &$errors);

}