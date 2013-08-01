<?php

namespace F8\Document;
use F8\Document;

class MongoDB extends Document {

    protected $_collection;
    public $_id;

    /**
     * For finding more than one document
     *
     * @param array $options
     * @param array $errors
     * @return Document[]
     */
    public function search($options, &$errors)
    {
        // TODO: Implement search() method.
    }

    /**
     * For creating a document
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    public function create($options, &$errors)
    {
        // TODO: Implement create() method.
    }

    /**
     * For reading one document
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    public function read($options, &$errors)
    {
        $options = array_merge(array(
            'fit_strict' => false,
        ), $options);

        /** @var \MongoDB $db */
        $db = $this->_router->getConnection();
        $collection = $db->selectCollection($this->_collection);

        $record = $collection->findOne(array("_id"=>$this->_id));
        if (is_null($record)) {
            $errors[] = new Error($this->_router, _("Document Not Found"), 803001);
        } else {
            $this->fit($record, $options['fit_strict']);
        }

        return $this;
    }

    /**
     * For updating one document
     * Most commonly, it will be the current document.
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    public function update($options, &$errors)
    {
        // TODO: Implement update() method.
    }

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
    public function delete($options, &$errors)
    {
        // TODO: Implement delete() method.
    }


}