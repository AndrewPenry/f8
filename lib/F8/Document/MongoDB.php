<?php

namespace F8\Document;
use F8\Document;


trait MongoDB {

    // TODO make this into abstract functions getMongoId and setMongoID
    public $_id;

    /**
     * Return the value for _id or null
     * @return mixed
     */
    abstract function getMongoCollection();

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
        /** @var \F8\Router $r */
        $r = $this->_router;
        /** @var \MongoDB $db */
        $db = $r->getConnection($errors);
        $collection = $db->selectCollection($this->getMongoCollection());

        $document = $r->objectToArray($this);

        if (is_null($document['_id'])){
            unset($document['_id']);
        }

        try {
            if ($collection->insert($document)) {
                $this->_id = $document['_id'];
            } else {
                $errors[] = $r->messageFactory->message(_("Document could not be created"), 803002, array('document-type'=>get_class($this)));
            }
        } catch (\MongoException $e) {
            $r->logger->error('Mongo Exception', ['exception' => $e->getMessage()]);
            $errors[] = $r->messageFactory->message(_("Document could not be created"), 803002, array('document-type'=>get_class($this)));
        }

        return $this;
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
        $options = array_merge([
                'fit_strict' => false,
                'query' => ["_id"=>$this->_id],
                'fields' => [],
            ], $options);

        /** @var \F8\Router $r */
        $r = $this->_router;
        /** @var \MongoDB $db */
        $db = $this->_router->getConnection($errors);
        $collection = $db->selectCollection($this->getMongoCollection());

        $document = $collection->findOne($options['query'], $options['fields']);
        if (is_null($document)) {
            $errors[] = $r->messageFactory->message(_("Document Not Found"), 803001, array('document-type'=>get_class($this)));
        } else {
            $this->fit($document, $options['fit_strict']);
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


    /**
     * For saving one document.
     * Most commonly, it will be the current document.
     * Saving is a shortcut for create if new, otherwise update.
     * MongoDB has a save function that checks to see if the _id is set
     *
     * @param array $options
     * @param array $errors
     * @return Document
     */
    public function save($options, &$errors)
    {
        /** @var \F8\Router $r */
        $r = $this->_router;
        /** @var \MongoDB $db */
        $db = $r->getConnection($errors);
        $collection = $db->selectCollection($this->getMongoCollection());

        $document = $this->_router->objectToArray($this);

        if (is_null($document['_id'])){
            unset($document['_id']);
        }

        try {
            if ($collection->save($document)) {
                $this->_id = $document['_id'];
            } else {
                $errors[] = $r->messageFactory->message(_("Document could not be saved"), 803004, array('document-type'=>get_class($this)));
            }
        } catch (\MongoException $e) {
            $r->logger->error('Mongo Exception', ['exception' => $e->getMessage()]);
            $errors[] = $r->messageFactory->message(_("Document could not be saved"), 803004, array('document-type'=>get_class($this)));
        }

        return $this;

        // TODO: Implement save() method.
    }



}