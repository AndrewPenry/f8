# Setting up the App

## Directory Structrure

Bare minimum to get a "Hello World";

    .htaccess
    composer.json
    index.php
    App/
        Document.php
        Controller/
            Index.php
        Document/
        Service/
        View/
    assets/


More likely, you are going to want to override many of f8's base classes. A typical structure will look like:

    .htaccess
    composer.json
    index.php
    App/
        Controller.php
        CurrentUser.php
        Document.php
        Locator.php
        Router.php
        ViewSwitch.php
        Config/
            ...
        Controller/
            Index.php
            ...
        Document/
            ...
        Service/
            ...
        View/
            twig/
                index/
                    view.twig
                    ...
                ...
    assets/


In theory, `App`, `vendor`, and `tmp` directories could be outside the web root. This is not easy on Plesk, but may be
perferred on platforms like Heroku and would be more secure. In this case, index.php would be the ONLY php file in the
web root. All other files would be static assets in the `assets` folder. This would need changes to the composer.json
file.

## .htaccess

The server file needs to rewrite all non-matching requests to index.php. This has a side effect that both "app" and
"vendor" are reserved, and can not be used as controller names. 

    :::apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-l
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule .* index.php [L,QSA,E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

## nginx.conf

If you are using nginx instead of apache, this is equivilent to the .htaccess rewrite:

    :::nginx
    index index.php;
    
    location / {
        # try to serve file directly, fallback to rewrite
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass heroku-fcgi;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

This example assumes you are using heroku.

## composer.json

@todo: change this to use a real branch, and the packagist repo with first stable release.

The autoload line is very important. It will make the autoloader look for things in the *App* namespace in the
`App` directory.

    :::javascript
    {
        "require": {
            "f8/f8": "dev-master"
        },
        "repositories": [
            {
                "type": "vcs",
                "url": "git@github.com:AndrewPenry/f8.git"
            }
        ],
        "autoload": {
            "psr-0": {
                "App": ""
            }
        }
    }


## index.php

The minimal index simply needs to call the autoload, set up a couple services, initalize the router, and go. You must
initalize any PSR-3 logger and pass it as the first parameter to `F8\Router`

    :::php
    <?php
    require_once('vendor/autoload.php');
    
    // Initalize Router
    $logger = new \Monolog\Logger('app');
    $viewSwitch = new \App\ViewSwitch();
    $r = new \App\Router($logger, $viewSwitch);
    
    // Register global services
    $locator = \F8\Locator::getInstance();
    $locator->register('logger', 'app', $logger);
    $locator->register('router', 'app', $r);
    
    // Go!
    $r->go();