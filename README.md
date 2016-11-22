Github WebHook Stack middleware
==================

[![Build
Status](https://secure.travis-ci.org/Swop/github-webhook-stack.png?branch=master)](http://travis-ci.org/Swop/github-webhook-stack)

[Stack](http://stackphp.com) middleware to restrict application access to GitHub Event bot with signed payload.

Every incoming request will see its `X-Hub-Signature` header checked in order to validate that the request was originally performed by GitHub.
Any requests which doesn't have correct signature will lead to a `401 Unauthorized` JSON response.

Installation
------------

The recommended way to install this library is through [Composer](https://getcomposer.org/):

```
composer require "swop/github-webhook-stack"
```

Usage
------------
### Silex example
```php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new \Silex\Application();

$app->get('/', function(Request $request) {
    return new Response('Hello world!', 200);
});

$app = (new \Stack\Builder())
    ->push('Swop\GitHubWebHookStackPHP\GitHubWebHook', 'my_secret')
    ->resolve($app)
;

$request = Request::createFromGlobals();
$response = $app->handle($request)->send();

$app->terminate($request, $response);
```

### Symfony example
```php
# web/app_dev.php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
Debug::enable();

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();

$stack = (new Stack\Builder())
    ->push('Swop\GitHubWebHookStackPHP\GitHubWebHook', 'my_secret')
;

$kernel = $stack->resolve($kernel);

Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
```

Contributing
------------

See [CONTRIBUTING](https://github.com/Swop/github-webhook-stack.png/blob/master/CONTRIBUTING.md) file.

Original Credits
------------

* [Sylvain MAUDUIT](https://github.com/Swop) ([@Swop](https://twitter.com/Swop)) as main author.


License
------------

This library is released under the MIT license. See the complete license in the bundled [LICENSE](https://github.com/Swop/github-webhook-stack.png/blob/master/LICENSE) file.
