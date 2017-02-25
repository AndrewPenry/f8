<?php
namespace F8\Service;

interface DBInterface
{

    public function __construct(string $dbName, string $uri, array $uriOptions, array $driverOptions);

    public function client();

    public function db();

    /**
     * Inserts a new document into the collection
     *
     * @param string    $collectionName
     * @param object    $document
     * @param bool|null $strict
     *
     * @return bool
     */
    public function create(string $collectionName, $document, bool $strict = null): bool;

    /**
     * Reads a document by _id from the collection
     *
     * @param string    $collectionName
     * @param object    $document
     * @param bool|null $strict
     *
     * @return bool
     */
    public function read(string $collectionName, $document, bool $strict = null): bool;

    /**
     * Updates only those fields in document that are in the fields list.
     *
     * @param string    $collectionName
     * @param object    $document
     * @param array     $fields
     * @param bool|null $strict
     *
     * @return bool
     */
    public function update(string $collectionName, $document, array $fields, bool $strict = null): bool;

    /**
     * Deletes a record from the collection by _id
     *
     * @param string $collectionName
     * @param object $document
     *
     * @return bool
     */
    public function delete(string $collectionName, $document): bool;


}