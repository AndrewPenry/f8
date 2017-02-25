<?php
namespace F8\Service;

class NullDB implements DBInterface
{
    public function __construct(
        string $dbName = '',
        string $uri = '',
        array $uriOptions = [],
        array $driverOptions = []
    )
    {
    }

    public function client() { }

    public function db() { }

    public function create(string $collectionName, $document, bool $strict = null): bool { return false; }

    public function read(string $collectionName, $document, bool $strict = null): bool { return false; }

    public function update(
        string $collectionName,
        $document,
        array $fields,
        bool $strict = null
    ): bool
    {
        return false;
    }

    public function delete(string $collectionName, $document): bool { return false; }
}