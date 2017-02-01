<?php
namespace F8\Service;

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Model\BSONDocument;

class MongoDB7 implements DBInterface
{

    private $_client;
    private $_db;

    private static $defaultTypeMap = [
        'array' => 'array',
        'document' => 'array',
        'root' => 'array',
    ];

    public $strict = false;

    public function __construct(string $dbName, string $uri = 'mongodb://127.0.0.1/', array $uriOptions = [], array $driverOptions = [])
    {

        $driverOptions += ['typeMap' => self::$defaultTypeMap];

        $this->_client = new Client($uri, $uriOptions, $driverOptions);
        $this->_db = $this->_client->selectDatabase($dbName);
    }

    public function client() { return $this->_client; }

    public function db() { return $this->_db; }

    /**
     * Inserts a new document into the collection
     *
     * @param string $collectionName
     * @param object $document
     * @param bool|null $strict
     * @return bool
     */
    public function create(string $collectionName, $document, bool $strict = null):bool {
        $collection = $this->_db->selectCollection($collectionName);
        $document->_id = self::id($document->_id);

        $array = self::unfit($document, $strict ?? $this->strict);
        $result = $collection->insertOne($array);

        $document->_id = $result->getInsertedId();
        return true;
    }

    /**
     * Reads a document by _id from the collection
     *
     * @param string $collectionName
     * @param object $document
     * @param bool|null $strict
     * @return bool
     */
    public function read(string $collectionName, $document, bool $strict = null):bool {
        $collection = $this->_db->selectCollection($collectionName);

        $query = ['_id' => self::id($document->_id)];

        $result = $collection->findOne($query);
        if ($result) {
            self::fit($document, $result, $strict ?? $this->strict);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Updates only those fields in document that are in the fields list. Updates by _id
     *
     * @param string $collectionName
     * @param object $document
     * @param array $fields
     * @param bool|null $strict
     * @return bool
     */
    public function update(string $collectionName, $document, array $fields, bool $strict = null):bool {
        $strict = $strict ?? $this->strict;

        $collection = $this->_db->selectCollection($collectionName);

        $query = ['_id' => self::id($document->_id)];

        $props = [];
        if ($strict) {
            $reflection = new \ReflectionClass($document);
            $r_props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($r_props as $p) {
                $props[] = $p->getName();
            }
        }

        $set = [];
        $unset = [];
        foreach ($fields as $key) {
            if ($strict) {
                if (!in_array($key, $props)) {
                    continue;
                }
            }
            if (is_null($document->$key)){
                $unset[$key] = 1;
            } else {
                $set[$key] = self::BSONize($document->$key);
            }

        }

        $update = [];
        if (count($set)) $update['$set'] = $set;
        if (count($unset)) $update['$unset'] = $unset;
        if (count($update)) {
            $collection->updateOne($query, $update);
        }

        return true;
    }

    /**
     * Deletes a record from the collection by _id
     *
     * @param string $collectionName
     * @param object $document
     * @return bool
     */
    public function delete(string $collectionName, $document):bool {
        $collection = $this->_db->selectCollection($collectionName);
        $query = ['_id' => self::id($document->_id)];
        $result = $collection->deleteOne($query);
        return (bool) $result->getDeletedCount();
    }


    /**
     * Packs data from a result array into an object
     *
     * If strict is set to true, the key must match a public property of the Document. Doing so is slower (due to
     * reflection), so it is only recommended during debugging and development.
     *
     * @param object $document
     * @param array|BSONDocument $result
     * @param bool $strict
     * @return \stdClass $document
     */
    static public function fit( $document, $result, bool $strict) {

        $props = [];
        if ($strict) {
            $reflection = new \ReflectionClass($document);
            $r_props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($r_props as $p) {
                $props[] = $p->getName();
            }
        }

        foreach ($result as $key => $value){
            if ($strict) {
                if (!in_array($key, $props)) {
                    continue;
                }
            }
            $document->$key = self::phpize($value);
        }

        return $document;
    }

    /**
     * Packs data from an Object into an associative array that has been BSONized.
     *
     * If strict is set to true, the key must match a public property of the Document. Doing so is slower (due to
     * reflection), so it is only recommended during debugging and development.
     *
     * @param $document
     * @param bool $strict
     * @return array
     */

    static public function unfit($document, bool $strict): array {
        $props = [];
        if ($strict) {
            $reflection = new \ReflectionClass($document);
            $r_props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($r_props as $p) {
                $props[] = $p->getName();
            }
        }


        $array = [];
        foreach ($document as $key => $value) {
            if ($strict) {
                if (!in_array($key, $props)) {
                    continue;
                }
            }
            if (is_null($value)) continue;
            $array[$key] = self::BSONize($value);
        }
        return $array;
    }

    /**
     * Recursively converts some BSON classes to PHP classes
     *
     * @param $subject
     * @return mixed
     */
    static public function BSONize($subject){
        if (is_array($subject)) {
            foreach ($subject as $key => &$value) {
                $value = self::BSONize($value);
            }
        } elseif (is_object($subject)) {
            if ($subject instanceof \DateTime) {
                return new UTCDateTime($subject->getTimestamp()*1000);
            }
        }
        return $subject;
    }

    /**
     * Recursively converts some PHP classes to BSON classes
     *
     * @param $subject
     * @return mixed
     */
    static public function phpize($subject){
        if (is_array($subject)) {
            foreach ($subject as $key => &$value) {
                $value = self::phpize($value);
            }
        } elseif (is_object($subject)) {
            if ($subject instanceof UTCDateTime) {
                return $subject->toDateTime();
            }
        }
        return $subject;
    }

    /**
     * This is a convenience function so that lazy programmers don't need to know if the ID is currently an ObjectID or
     * a string that should be an ObjectId or just a scalar that should be used instead of an ObjectId. Pass it in and
     * the right type comes back out.
     *
     * WARNING! It will convert any 24 length hexadecimal number to an ObjectID. So if you were using something like
     * user names as your _id (which you should not) the a user named faddaef12345678901234567 would have his string
     * name converted to an ObjectID.
     *
     * @param $id
     * @return ObjectID
     */
    static public function id($id = null) {
        if ($id instanceof ObjectID) {
            return $id;
        }
        if (ctype_xdigit($id) && strlen($id) == 24) {
            return new ObjectID($id);
        }
        return $id;
    }


}