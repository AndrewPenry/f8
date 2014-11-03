<?php

namespace F8\Document;
use F8\Document;

/**
 * Trait MongoDB
 * @package F8\Document
 *
 * This trait should only be applied to classes that extend \F8\Document.
 * It is a default implementation of the abstract methods found in \F8\Document.
 *
 * @property \F8\Router $_router;
 */
trait MongoDB {

    // @TODO make this into abstract functions getMongoId and setMongoID ?
    public $_id;

    /**
     * Return the value for collection or null
     * @return mixed
     */
    abstract function getMongoCollection();

    /**
     * For finding more than one document
     *
     * @param array $options
     * @param array $errors
     * @param int $count
     * @param \MongoDB $db
     * @return Document[]
     */
    public function search($options, &$errors, &$count = null, $db = null)
    {
        $options = array_merge([
                'fit_strict' => false,
                'fields' => [],
                'limit' => 0,
                'sort' => [],
                'skip' => 0,
            ], $options);

        // This is not just part of the default options because it could be a slow function. If the query is passed in, then there is no point running it, just to be overridden.
        if (!isset($options['query'])) {
            $options['query'] = $this->_router->objectToArray($this, true);
        }

        /** @var \F8\Router $r */
        $r = $this->_router;
        /** @var \MongoDB $db */
        if (is_null($db)) $db = $r->getConnection('mongo', $errors);
        $collection = $db->selectCollection($this->getMongoCollection());

        $cursor = $collection->find($options['query'], $options['fields']);
        if ($options['sort']) {
            $cursor->sort($options['sort']);
        }
        if ((int) $options['limit'] > 0) {
            $cursor->limit((int) $options['limit']);
        }
        if ((int) $options['skip'] > 0) {
            $cursor->skip((int) $options['skip']);
        }

        $count = $cursor->count();

        $objects = [];
        foreach ($cursor as $document) {
            $object = clone $this;
            $object->fit($document, $options['fit_strict']);
            $objects[] = $object;
        }

        return $objects;

    }

    /**
     * For creating a document
     *
     * @param array $options
     * @param array $errors
     * @param \MongoDB $db
     * @return Document
     */
    public function create($options, &$errors, $db = null)
    {
        /** @var \F8\Router $r */
        $r = $this->_router;
        if (is_null($db)) $db = $r->getConnection('mongo', $errors);
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
     * @param \MongoDB $db
     * @return Document
     */
    public function read($options, &$errors, $db = null)
    {
        $options = array_merge([
                'fit_strict' => false,
                'query' => ["_id"=>$this->_id],
                'fields' => [],
            ], $options);

        /** @var \F8\Router $r */
        $r = $this->_router;
        /** @var \MongoDB $db */
        if (is_null($db)) $db = $r->getConnection('mongo', $errors);
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
     * @param \MongoDB $db
     * @return Document
     */
    public function update($options, &$errors, $db = null)
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
     * @param \MongoDB $db
     * @return boolean
     */
    public function delete($options, &$errors, $db = null)
    {
        $options = array_merge([
                'query' => ["_id"=>$this->_id],
            ], $options);

        /** @var \F8\Router $r */
        $r = $this->_router;
        /** @var \MongoDB $db */
        if (is_null($db)) $db = $r->getConnection('mongo', $errors);
        $collection = $db->selectCollection($this->getMongoCollection());

        return $collection->remove($options['query']);
    }


    /**
     * For saving one document.
     * Most commonly, it will be the current document.
     * Saving is a shortcut for create if new, otherwise update.
     * MongoDB has a save function that checks to see if the _id is set
     *
     * @param array $options
     * @param array $errors
     * @param \MongoDB $db
     * @return Document
     */
    public function save($options, &$errors, $db = null)
    {
        /** @var \F8\Router $r */
        $r = $this->_router;
        /** @var \MongoDB $db */
        try {
            if (is_null($db)) $db = $r->getConnection('mongo', $errors);
            $collection = $db->selectCollection($this->getMongoCollection());

            $document = $this->_router->objectToArray($this);
            if (is_null($document['_id'])){
                unset($document['_id']);
            }

            if ($collection->save($document)) {
                $this->_id = $document['_id'];
            } else {
                $errors[] = $r->messageFactory->message(_("Document could not be saved"), 803004, array('document-type'=>get_class($this)));
            }
        } catch (\MongoException $e) {
            $r->logger->error('Mongo Exception', ['exception' => $e->getMessage()]);
            $errors[] = $r->messageFactory->message(_("Document could not be saved"), 803004, array('document-type'=>get_class($this), 'mongoException' => $e));
        }

        return $this;
    }

    public function expandMongoRef($paramName, $className, $readOptions, &$errors, $db = null) {
        $param = $this->$paramName;
        $object = new $className($this->_router);
        $object->_id = $param["_id"];
        $object->read($readOptions, $errors, $db);
        $this->$paramName = $object;
        return $this;
    }


}