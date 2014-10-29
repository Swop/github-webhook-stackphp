<?php
namespace Swop\Stack;

use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class GitHubWebHookTest extends \PHPUnit_Framework_TestCase
{
    /** @var  PHPUnit_Framework_MockObject_MockObject|Request */
    private $requestStub;
    /** @var  PHPUnit_Framework_MockObject_MockObject|HeaderBag */
    private $headerBagStub;
    /** @var  PHPUnit_Framework_MockObject_MockObject|Response */
    private $responseStub;
    /** @var  PHPUnit_Framework_MockObject_MockObject|HttpKernelInterface */
    private $kernelStub;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->requestStub = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerBagStub = $this->getMockBuilder('Symfony\Component\HttpFoundation\HeaderBag')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStub->headers = $this->headerBagStub;
//        $this->requestStub->expects($this->at(0))
//            ->method('__get')
//            ->with($this->equalTo('headers'))
//            ->will($this->returnValue($this->headerBagStub));

        $this->responseStub = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernelStub = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    }

    /**
     * @dataProvider correctSignatures
     */
    public function testCorrectSignature($requestContent, $requestSignature, $gitHubWebHookSecret)
    {
        $this->configureHeaderBagStub($requestSignature);
        $this->configureRequestStub($requestContent);

        $this->kernelStub->expects($this->once())
            ->method('handle')
            ->will($this->returnValue($this->responseStub));

        $stackGitHubWebHook = new GitHubWebHook($this->kernelStub, $gitHubWebHookSecret);
        $response = $stackGitHubWebHook->handle($this->requestStub);

        $this->assertEquals($response, $this->responseStub);
    }

    /**
     * @dataProvider incorrectSignatures
     */
    public function testIncorrectSignature($requestContent, $requestSignature, $gitHubWebHookSecret)
    {
        $this->configureHeaderBagStub($requestSignature);
        $this->configureRequestStub($requestContent);

        $this->kernelStub->expects($this->never())
            ->method('handle');

        $stackGitHubWebHook = new GitHubWebHook($this->kernelStub, $gitHubWebHookSecret);
        $response = $stackGitHubWebHook->handle($this->requestStub);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(array('error' => 401, 'message' => 'Unauthorized'), json_decode($response->getContent(), true));
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

    private function configureHeaderBagStub($requestSignature)
    {
        $this->headerBagStub->expects($this->once())
            ->method('get')
            ->with($this->equalTo('X-Hub-Signature'))
            ->will($this->returnValue($requestSignature));
    }

    private function configureRequestStub($requestContent)
    {
        $this->requestStub->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($requestContent));
    }
}
