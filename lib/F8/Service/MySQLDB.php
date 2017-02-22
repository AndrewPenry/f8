<?php
namespace F8\Service;

class MySQLDB implements DBInterface
{

    private $_client;
    private $_db;

    public $strict = false;

    protected $_config = [];
    protected $_connected = false;

    public function __construct(string $dbName, string $uri = 'mysql:host=localhost', array $uriOptions = [], array $driverOptions = [])
    {
        $this->_config = array(
            'dsn' => $uri.';'.$dbName,
            'username' => $uriOptions['username'],
            'password' => $uriOptions['password'],
        );
    }

    /**
     * Lazy Loading
     */
    private function checkConnection() {
        if (!$this->_connected) {
            $this->_client = new \PDO($this->_config['dsn'], $this->_config['username'], $this->_config['password']);
            $this->_db = $this->_client;
            $this->_connected = true;
        }
    }

    public function client() {
        $this->checkConnection();
        return $this->_client;
    }

    public function db() {
        $this->checkConnection();
        return $this->_db;
    }

    /**
     * Inserts a new document into the collection
     *
     * @param string $collectionName
     * @param object $document
     * @param bool|null $strict
     * @return bool
     */
    public function create(string $collectionName, $document, bool $strict = null):bool {
        // @todo Implement MySQLDB::create
        return false;
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
        // @todo Implement MySQLDB::read
        return false;
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
        // @todo Implement MySQLDB::update
        return false;
    }

    /**
     * Deletes a record from the collection by _id
     *
     * @param string $collectionName
     * @param object $document
     * @return bool
     */
    public function delete(string $collectionName, $document):bool {
        // @todo Implement MySQLDB::delete
        return false;
    }


    /**
     * Packs data from a result array into an object
     *
     * If strict is set to true, the key must match a public property of the Document. Doing so is slower (due to
     * reflection), so it is only recommended during debugging and development.
     *
     * @param object $document
     * @param array $result
     * @param bool $strict
     * @return \stdClass $document
     */
    static public function fit( $document, $result, bool $strict) {
        // @todo Implement MySQLDB::fit
        return null;
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
        // @todo Implement MySQLDB::unfit
        return null;
    }


}