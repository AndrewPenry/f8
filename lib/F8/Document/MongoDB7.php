<?php

namespace F8\Document;
use F8\Document;
use F8\ErrorFactory;
use F8\Locator;

/**
 * Trait MongoDB7
 * @package F8\Document
 *
 * This trait should be applied to classes that extend \F8\Document.
 * It is a default implementation of the abstract methods found in \F8\Document.
 *
 * @property \F8\Router $_router;
 */
trait MongoDB7 {

    public $_id;

    /**
     * Return the name of the collection
     * @return string
     */
    abstract public function getMongoCollection(): string;

    /**
     * For finding more than one document
     *
     * F8 Options:
     *  class_field     string. Will cast results into the value found in this field
     *  fit_strict      bool. Will only fill in public properties of the object, used for development
     *
     * Mongo Options:
     *  query           Mongo filter criteria. Defaults to searching by document contents.
     *  projection      Mongo field projection. If empty, will read all fields.
     *  skip            int. Number of documents to skip.
     *  sort            Mongo sort specification.
     *  collation       array. Allows for custom sorting. See https://docs.mongodb.com/manual/reference/collation/#collation-document
     *  comment         string. For profiling.
     *  maxTimeMS       int. Time limit for processing cursor
     *
     * Deprecated options
     *  fields          Mongo field projection, but only positive. Use projection instead
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param int $count
     * @param \MongoDB $db
     * @return Document[]
     */
    public function search($options, &$errors, &$count = null, $db = null)
    {
        $locator = Locator::getInstance();
        /** @var \F8\Service\MongoDB7 $db */
        $db = $db ?? $locator->db('mongo');

        // This is not just part of the default options because it could be a slow function.
        // If the query is passed in, then there is no point running it, just to be overridden.
        if (!isset($options['query'])) {
            $options['query'] = \F8\Service\MongoDB7::unfit($this, $options['fit_strict'] ?? $db->strict);
            if(isset($options['query']['_id'])) $options['query']['_id'] = \F8\Service\MongoDB7::id($options['query']['_id']);
        }

        $driverOptions = $this->_driverOptions($options);
        $objects = [];

        try {
            $collection = $db->db()->selectCollection($this->getMongoCollection());

            $count = $collection->count($options['query'], $driverOptions);

            $cursor = $collection->find($options['query'], $driverOptions);
            foreach ($cursor as $result) {
                if (isset($options['class_field']) && $c = @$result[$options['class_field']]) {
                    if (class_exists($c)) {
                        $new = new $c($this->_router);
                        \F8\Service\MongoDB7::fit($new, $result, $options['fit_strict'] ?? $db->strict);
                        $objects[] = $new;
                    }
                } else {
                    $object = clone $this;
                    \F8\Service\MongoDB7::fit($object, $result, $options['fit_strict'] ?? $db->strict);
                    $objects[] = $object;
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            $locator->logger('f8')->error('Mongo Exception', ['exception' => $e->getMessage(), 'code' => $e->getCode()]);
            /** @var ErrorFactory $mf */
            $mf = $locator->locate('messageFactory', 'app');
            $errors[] = $mf->message(_("Error while searching"), 803001, array('document-type'=>get_class($this)));
        }

        return $objects;
    }

    /**
     * For creating a document
     *
     * F8 Options:
     *  fit_strict      bool. Will only fill in public properties of the object, used for development
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param \F8\Service\MongoDB7 $db
     * @return Document
     */
    public function create(array $options = [], &$errors = [], $db = null)
    {
        $locator = Locator::getInstance();
        /** @var \F8\Service\MongoDB7 $db */
        $db = $db ?? $locator->db('mongo');

        try {
            $db->create($this->getMongoCollection(), $this, $options['fit_strict'] ?? $db->strict);
        } catch (\Exception $e) {
            echo $e->getMessage();
            $locator->logger('f8')->error('Mongo Exception', ['exception' => $e->getMessage(), 'code' => $e->getCode()]);
            /** @var ErrorFactory $mf */
            $mf = $locator->locate('messageFactory', 'app');
            $errors[] = $mf->message(_("Document could not be created"), 803002, array('document-type'=>get_class($this)));
        }

        /** @var Document $this */
        return $this;
    }

    /**
     * For reading one document
     *
     * F8 Options:
     *  class_field     string. Will cast result into the value found in this field
     *  fit_strict      bool. Will only fill in public properties of the object, used for development
     *
     * Mongo Options:
     *  query           Mongo filter criteria. Defaults to searching by document _id.
     *  projection      Mongo field projection. If empty, will read all fields.
     *  skip            int. Number of documents to skip.
     *  sort            Mongo sort specification.
     *  collation       array. Allows for custom sorting. See https://docs.mongodb.com/manual/reference/collation/#collation-document
     *  comment         string. For profiling.
     *  maxTimeMS       int. Time limit for processing cursor
     *
     * Deprecated options
     *  fields          Mongo field projection, but only positive. Use projection instead
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param \F8\Service\MongoDB7 $db
     * @return Document
     */
    public function read($options, &$errors = [], $db = null)
    {
        $locator = Locator::getInstance();
        /** @var \F8\Service\MongoDB7 $db */
        $db = $db ?? $locator->db('mongo');
        /** @var ErrorFactory $mf */
        $mf = $locator->locate('messageFactory', 'app');

        try {

            // Empty Options = Simple Read and Fit
            if (empty($options)) {
                    if ($db->read($this->getMongoCollection(), $this) === false) {
                        $errors[] = $mf->message(_("Document Not Found"), 803001, array('document-type'=>get_class($this)));
                    }
                /** @var Document $this */
                return $this;
            }

            // Non-Empty Options = Custom Read and Fit
            $options = array_merge([
                'query' => ["_id"=>\F8\Service\MongoDB7::id($this->_id)],
            ], $options);

            $driverOptions = $this->_driverOptions($options);

            $collection = $db->db()->selectCollection($this->getMongoCollection());

            $result = $collection->findOne($options['query'], $driverOptions);
            if ($result) {
                if (isset($options['class_field']) && $c = @$result[$options['class_field']]) {
                    if (class_exists($c)) {
                        $new = new $c($this->_router);
                        \F8\Service\MongoDB7::fit($new, $result, $options['fit_strict'] ?? $db->strict);
                        return $new;
                    }
                }
                \F8\Service\MongoDB7::fit($this, $result, $options['fit_strict'] ?? $db->strict);
            } else {
                $errors[] = $mf->message(_("Document Not Found"), 803001, array('document-type'=>get_class($this)));
            }
            /** @var Document $this */
            return $this;

        } catch (\Exception $e) {
            echo $e->getMessage();
            $locator->logger('f8')->error('Mongo Exception', ['exception' => $e->getMessage(), 'code' => $e->getCode()]);
            $errors[] = $mf->message(_("Document Not Found"), 803001, array('document-type'=>get_class($this)));
            /** @var Document $this */
            return $this;
        }
    }

    /**
     * For updating one document
     * Most commonly, it will be the current document.
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param \MongoDB $db
     * @return Document
     */
    public function update($options, &$errors, $db = null)
    {
        // TODO: Implement update() method.
        /** @var Document $this */
        return $this;
    }

    /**
     * For deleting one document
     *
     * Mongo Options:
     *  query           Mongo filter criteria. Defaults to searching by document _id.
     *  collation       array. Allows for custom sorting. See https://docs.mongodb.com/manual/reference/collation/#collation-document
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param \F8\Service\MongoDB7 $db
     * @return bool
     */
    public function delete($options, &$errors, $db = null)
    {
        $locator = Locator::getInstance();
        /** @var \F8\Service\MongoDB7 $db */
        $db = $db ?? $locator->db('mongo');
        /** @var ErrorFactory $mf */
        $mf = $locator->locate('messageFactory', 'app');

        try {
            // Empty Options = Simple Delete
            if (empty($options)) {
                if ($db->delete($this->getMongoCollection(), $this) === false) {
                    $errors[] = $mf->message(_("Document Not Deleted"), 803008, array('document-type'=>get_class($this)));
                    return false;
                }
                return true;
            }

            // Non-Empty Options = Custom Delete
            $options = array_merge([
                'query' => ["_id"=>\F8\Service\MongoDB7::id($this->_id)],
            ], $options);
            $driverOptions = $this->_driverOptions($options);

            $collection = $db->db()->selectCollection($this->getMongoCollection());
            $result = $collection->deleteOne($options['query'], $driverOptions);
            if ($result->getDeletedCount()) {
                return true;
            } else {
                $errors[] = $mf->message(_("Document Not Deleted"), 803008, array('document-type'=>get_class($this)));
                return false;
            }
        } catch (\Exception $e) {
            $locator->logger('f8')->error('Mongo Exception', ['exception' => $e->getMessage(), 'code' => $e->getCode()]);
            $errors[] = $mf->message(_("Document Not Deleted"), 803008, array('document-type'=>get_class($this)));
            return false;
        }
    }


    /**
     * For saving one document
     *
     * F8 Options:
     *  fit_strict      bool. Will only fill in public properties of the object, used for development
     *
     * Mongo Options:
     *  query           Mongo filter criteria. Defaults to searching by document _id.
     *  upsert          bool. Tells mongo to make a new document when _id is not found. Default true.
     *  collation       array. Allows for custom sorting. See https://docs.mongodb.com/manual/reference/collation/#collation-document
     *
     * @param array $options
     * @param \F8\Error[] $errors
     * @param \F8\Service\MongoDB7 $db
     * @return Document
     */
    public function save($options, &$errors, $db = null)
    {
        if (is_null($this->_id)) {
            return $this->create($options, $errors, $db);
        }

        $locator = Locator::getInstance();
        /** @var \F8\Service\MongoDB7 $db */
        $db = $db ?? $locator->db('mongo');
        /** @var ErrorFactory $mf */
        $mf = $locator->locate('messageFactory', 'app');

        try {
            // Non-Empty Options = Custom Delete
            $options = array_merge([
                'query' => ["_id"=>\F8\Service\MongoDB7::id($this->_id)],
                'upsert' => true,
            ], $options);
            $driverOptions = $this->_driverOptions($options);

            $collection = $db->db()->selectCollection($this->getMongoCollection());
            $array = \F8\Service\MongoDB7::unfit($this, $options['fit_strict'] ?? $db->strict);
            $result = $collection->replaceOne($options['query'], $array, $driverOptions);
            if ($result->getUpsertedCount()) {
                $this->_id = $result->getUpsertedId();
                /** @var Document $this */
                return $this;
            }

            if ($result->getModifiedCount()) {
                /** @var Document $this */
                return $this;
            } else {
                $errors[] = $mf->message(_("Document Not Modified"), 803006, array('document-type'=>get_class($this)));
                /** @var Document $this */
                return $this;
            }
        } catch (\Exception $e) {
            $locator->logger('f8')->error('Mongo Exception', ['exception' => $e->getMessage(), 'code' => $e->getCode()]);
            $errors[] = $mf->message(_("Document Not Modified"), 803006, array('document-type'=>get_class($this)));
            /** @var Document $this */
            return $this;
        }
    }

    /**
     * Expands a Reference by _id
     *
     * @param string $paramName
     * @param string $className
     * @param array $readOptions
     * @param \F8\Error[] $errors
     * @param \F8\Service\MongoDB7 $db
     * @return Document $this
     */
    public function expandRef(string $paramName, string $className, array $readOptions, &$errors, $db = null) {
        $param = $this->$paramName;
        /** @var MongoDB7 $object */
        $object = new $className($this->_router);
        if (!empty($param["_id"])) {
            $object->_id = $param["_id"];
            $object->read($readOptions, $errors, $db);
        }
        $this->$paramName = $object;
        /** @var Document $this */
        return $this;
    }

    /**
     * Collapses a document into just an _id reference
     *
     * @param string $paramName
     * @return Document $this
     */
    public function collapseRef(string $paramName) {
        $this->$paramName = ["_id" => $this->$paramName->_id];
        /** @var Document $this */
        return $this;
    }

    /**
     * Checks to see if the record exists
     *
     * Mongo Options:
     *  query           Mongo filter criteria. Defaults to searching by document _id.
     *
     * @param $options
     * @param $errors
     * @param null $db
     * @return bool
     */
    public function exists($options, &$errors, $db = null)
    {
        // Non-Empty Options = Custom Read and Fit
        $options = array_merge([
            'query' => ["_id"=>\F8\Service\MongoDB7::id($this->_id)],
        ], $options);

        $locator = Locator::getInstance();
        /** @var \F8\Service\MongoDB7 $db */
        $db = $db ?? $locator->db('mongo');
        /** @var ErrorFactory $mf */
        $mf = $locator->locate('messageFactory', 'app');

        $driverOptions = $this->_driverOptions($options);

        try {
            $collection = $db->db()->selectCollection($this->getMongoCollection());

            $count = $collection->count($options['query'], $driverOptions);

            return (bool)$count;
        }
        catch (\Exception $e) {
            echo $e->getMessage();
            $locator->logger('f8')->error('Mongo Exception', ['exception' => $e->getMessage(), 'code' => $e->getCode()]);
            $errors[] = $mf->message(_("Document Not Found"), 803001, array('document-type'=>get_class($this)));
            /** @var Document $this */
            return false;
        }
    }





    /**
     * Limits the options to just the driver options, instead of the f8 options
     *
     * @param array
     * @return array
     */
    private function _driverOptions(array $options): array {

        $driverOptions = [];
        if (!empty($options['projection'])) { // PHP 7 MongoDB way
            $driverOptions['projection'] = $options['projection'] ;
        } elseif (!empty($options['fields'])) { // PHP 5 Mongo way
            $driverOptions['projection'] = array_fill_keys($options['fields'], 1);
        }

        if (!empty($options['sort'])) { // Both ways
            $driverOptions['sort'] = $options['sort'] ;
        }
        if (!empty($options['skip'])) { // Both ways
            $driverOptions['skip'] = $options['skip'] ;
        }
        if (!empty($options['limit'])) { // Both ways
            $driverOptions['limit'] = $options['limit'] ;
        }
        if (!empty($options['batchSize'])) { // PHP 7 Only
            $driverOptions['batchSize'] = $options['batchSize'] ;
        }
        if (!empty($options['collation'])) { // PHP 7 Only
            $driverOptions['collation'] = $options['collation'] ;
        }
        if (!empty($options['comment'])) { // PHP 7 Only
            $driverOptions['comment'] = $options['collation'] ;
        }
        if (!empty($options['maxTimeMS'])) { // PHP 7 Only
            $driverOptions['maxTimeMS'] = $options['maxTimeMS'] ;
        }

        if (!empty($options['upsert'])) { // PHP 7 Only
            $driverOptions['upsert'] = $options['upsert'] ;
        }

        return $driverOptions;
    }





    /**
     * Expands a Mongo Reference. Deprecated in favor of the generic expandRef, as the document shouldn't really need
     * to use mongo specific things.
     *
     * @deprecated Deprecated in favor of the generic expandRef
     * @param string $paramName
     * @param string $className
     * @param array $readOptions
     * @param \F8\Error[] $errors
     * @param \F8\Service\MongoDB7 $db
     * @return Document $this
     */
    public function expandMongoRef(string $paramName, string $className, array $readOptions, &$errors, $db = null) {
        return $this->expandRef($paramName, $className, $readOptions, $errors, $db);
    }


    /**
     * Collapses a document into just an _id reference.
     *
     * @deprecated Deprecated in favor of generic collapseRef.
     * @param string $paramName
     * @return Document $this
     */
    public function collapseMongoRef(string $paramName) {
        return $this->collapseRef($paramName);
    }


}