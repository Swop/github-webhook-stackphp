<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\GitHubWebHookStackPHP\Tests;

use Prophecy\Argument;
use Swop\GitHubWebHookStackPHP\GitHubWebHook;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubWebHookTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidRequestShouldReturnA401Response()
    {
        $next = $this->prophesize('Symfony\Component\HttpKernel\HttpKernelInterface');
        $next->handle(Argument::any())->shouldNotBeCalled();

        $request = Request::create('http://localhost/');

        $middleware = new GitHubWebHook($next->reveal(), 'my_secret');
        $response = $middleware->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(json_encode(array('error' => 401, 'message' => 'Unauthorized')), $response->getContent());
    }

    public function testValidRequestShouldBeHandledByTheNextMiddleware()
    {
        $content = '{"content": "This is the content"}';
        $request = Request::create('http://localhost/', 'GET', [], [], [], [], $content);
        $request->headers->set('X-Hub-Signature', sprintf('sha1=%s', hash_hmac('sha1', $content, 'my_secret')));

        $psrFactory = new DiactorosFactory();
        $foundationFactory = new HttpFoundationFactory();
        $psrRequest = $psrFactory->createRequest($request);
        $expectedRequest = $foundationFactory->createRequest($psrRequest);

        $response         = new Response('OK');
        $expectedResponse = $foundationFactory->createResponse($psrFactory->createResponse($response));

        $next = $this->prophesize('Symfony\Component\HttpKernel\HttpKernelInterface');
        $next->handle($expectedRequest)
            ->shouldBeCalledTimes(1)
            ->willReturn($response)
        ;

        $middleware = new GitHubWebHook($next->reveal(), 'my_secret');
        $response = $middleware->handle($request);

        $this->assertEquals($expectedResponse, $response);
    }
}
