<?php
namespace Swop\Stack;

use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class GitHubWebHookTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider correctSignatures
     */
    public function testCorrectSignature($requestContent, $requestSignature, $gitHubWebHookSecret)
    {
        $request = $this->createRequest($requestSignature, $requestContent);
        $expectedResponse = new Response();

        $kernel = $this->prophesize('Symfony\Component\HttpKernel\HttpKernelInterface');
        $kernel->handle(
            Argument::is($request),
            HttpKernelInterface::MASTER_REQUEST,
            true
        )
            ->shouldBeCalledTimes(1)
            ->willReturn($expectedResponse);

        $stackGitHubWebHook = new GitHubWebHook($kernel->reveal(), $gitHubWebHookSecret);
        $response = $stackGitHubWebHook->handle($request);

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @dataProvider incorrectSignatures
     */
    public function testIncorrectSignature($requestContent, $requestSignature, $gitHubWebHookSecret)
    {
        $request = $this->createRequest($requestSignature, $requestContent);

        $kernel = $this->prophesize('Symfony\Component\HttpKernel\HttpKernelInterface');
        $kernel->handle(Argument::any())->shouldNotBeCalled();

        $stackGitHubWebHook = new GitHubWebHook($kernel->reveal(), $gitHubWebHookSecret);
        $response = $stackGitHubWebHook->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(json_encode(array('error' => 401, 'message' => 'Unauthorized')), $response->getContent());
    }

    public function correctSignatures()
    {
        // $requestContent, $requestSignature, $gitHubWebHookSecret

        return array(
            array('{"foo": "bar"}', "sha1=" . hash_hmac('sha1', '{"foo": "bar"}', 'ThisIsMySecret'), 'ThisIsMySecret'),
            array('{"foo": "bar"}', "md5=" . hash_hmac('md5', '{"foo": "bar"}', 'ThisIsMyOtherSecret'), 'ThisIsMyOtherSecret'),
            array('{"foo": "bar", "baz": true}', "sha256=" . hash_hmac('sha256', '{"foo": "bar", "baz": true}', 'ThisIsMySecret'), 'ThisIsMySecret'),
        );
    }

    public function incorrectSignatures()
    {
        // $requestContent, $requestSignature, $gitHubWebHookSecret

        return array(
            array('{"foo": "bar"}', "sha1=WrongHash", 'ThisIsMySecret'),
            array('{"foo": "bar"}', null, 'ThisIsMySecret'),
            array('{"foo": "bar"}', "NoAlgorithm", 'ThisIsMySecret'),
        );
    }

    /**
     * @param string $requestSignature
     * @param string $requestContent
     *
     * @return Request
     */
    private function createRequest($requestSignature, $requestContent)
    {
        return Request::create(
            '/webhook',
            'GET',
            array(),
            array(),
            array(),
            array('HTTP_X_Hub_Signature' => $requestSignature),
            $requestContent
        );
    }
}
