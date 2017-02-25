# Known Issues

## Apache, Colon, Windows, and 403 Forbidden

URLs that have a colon in the first part of the path, like `http://example.com/test:foo` will end up with a 403 Forbidden in Windows, due to Windows interpreting the colon as a drive letter selector. On linux, this will go to the `Index` controller and call the `view()` method, with `$var['test']` set to "foo".

URLs with colons anywhere after the first seperator will be fine in both (ex. `http://example.com/index/test:foo`).

## Forward slash

Forward slashes in data, even encoded will lead to 404 errors. Example:

`http://example.com/doc/list/search-term:this%2Fthat`