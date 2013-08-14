# Error codes

All F8 error codes start with 8 and are 6 digits long. Error codes that start with 800 or 801 are Exception code numbers. Error codes starting with 802-808 are for Errors passed by reference.

## 800xxx

800 errors are logic exceptions. These kind of exceptions should directly lead to a fix in your code.

-  800001 malformed parameter, unknown source
-  800002 malformed f8 option (This is often thrown by validation functions if one of the options is malformed. For example, a malformed date set as the minimum date.)
-  800003 bad route

## 801xxx

801 errors are common security exceptions. These kind of exceptions most likely indicate tampering with HTTP Requests, or execution of automated scripts.

-  801001 token does not match hash
-  801002 excessive requests
-  801003 ip changed mid session

## 802xxx

The are HTTP errors. They probably don't need logged separately, as default logging of these should work.

-  802404 Not found

## 803xxx

803 errors are general DB and Docuement errors

-  803001 Document not found
-  803002 Failed to insert Document
-  803003 Failed to update Document
-  803004 Failed to save Document
-  803050 Invalid Query
-  803901 Could not connect to DB


## 808xxx

808 errors are validation errors. These are common errors and should always be handled in validator methods.

-  808001 not a string (not currently used)
-  808002 not a raw (not currently used)
-  808003 not an int
-  808004 not a valid date (failed regex)
-  808005 is before minimum date
-  808006 is after maximum date
-  808999 is set to Always Fail, this means there is an error in the code. (this is a type that can be used for testing)

## 809xxx

809 errors are for debugging during development.

-  809000 Default error code. Logging this can find places in the code where you forgot to assign an error code.
-  809001 mongo record has field that does not match with a property of the model. This is returned only if Document::fit(true) is called.
-  809999 is reserved for testing error catching.