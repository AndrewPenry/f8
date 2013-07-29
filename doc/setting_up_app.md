# Setting up the App

## Directory Structrure

.htaccess
composer.json
index.php
app (folder)
    Model.php
    Controller.php
    View.php
    model (folder)
    controller (folder)
        Index.php
    view (folder)

## .htaccess

The server file needs to rewrite all non-matching requests to index.php. This has a side effect that both "app" and "vendor" are reserved, and can not be used as contorller names.

    :::apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-l
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule .* index.php [L,QSA,E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]


## composer.json

TODO: change this to use a real branch, and the packagist repo with first stable release

The autoload line is very important. It will make the autoloader look for things in the *App* namespace in the `app` directory.

    :::javascript
    {
        "require": {
            "f8/f8": "dev-master"
        },
        "repositories": [
            {
                "type": "vcs",
                "url": "git@bitbucket.org:andrew_penry/f8.git"
            }
        ],
        "autoload": {
            "psr-0": {
                "App": ""
            }
        }
    }


## index.php

The minimal index simply needs to call the autoload, initalize the router, and go. You must initalize any PSR-3 logger and pass it as the first parameter to `F8\Router`

    :::php
    <?php
    require_once('vendor/autoload.php');
    $logger = new Monolog\Logger('default');
    $r = new F8\Router($logger);
    $r->go();

