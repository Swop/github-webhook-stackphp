# Github WebHook Stack middleware

[Stack](http://stackphp.com) middleware to restrict application access to GitHub Event bot with signed payload.

Every incoming request will have its X-Hub-Signature header checked in order to see if the request was originally performed by GitHub.
Any requests which doesn't have correct signature will provoke a _401 Unauthorized_ response.

## Usage
### Silex example
```php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app->get('/', function(Request $request) {
    return new Response('Hello world!', 200);
});

$app = (new Stack\Builder())
    ->push('Swop\Stack\GitHubWebHook', 'MyGitHubWebhookSecret')
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
    ->push('Swop\Stack\GitHubWebHook', 'MyGitHubWebhookSecret')

$kernel = $stack->resolve($kernel);

Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
```

## Intallation

The recommended way to install this library is through [Composer](http://getcomposer.org/):

``` json
{
    "require": {
        "swop/stack-github-webhook": "~1.0"
    }
}
```

## License

This library is released under the MIT License. See the bundled LICENSE file for details.
