<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\GitHubWebHookStackPHP;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * This Middleware implementation allow a compatibility between PSR-15 middlewares and StackPHP ones.
 *
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class Psr7MiddlewareBridge implements HttpKernelInterface, DelegateInterface
{
    /** @var HttpKernelInterface */
    private $next;
    /** @var ServerMiddlewareInterface */
    private $psrMiddleware;
    /** @var HttpMessageFactoryInterface */
    private $httpMessageFactory;
    /** @var HttpFoundationFactoryInterface */
    private $httpFoundationFactory;

    /**
     * @param HttpKernelInterface            $next                  Next middleware
     * @param ServerMiddlewareInterface      $psrMiddleware         Wrapped middleware
     * @param HttpMessageFactoryInterface    $httpMessageFactory    PSR-7 message factory
     * @param HttpFoundationFactoryInterface $httpFoundationFactory Symfony's Http Foundation request/response factory
     */
    public function __construct(
        HttpKernelInterface $next,
        ServerMiddlewareInterface $psrMiddleware,
        HttpMessageFactoryInterface $httpMessageFactory,
        HttpFoundationFactoryInterface $httpFoundationFactory
    ) {
        $this->next                  = $next;
        $this->psrMiddleware         = $psrMiddleware;
        $this->httpMessageFactory    = $httpMessageFactory;
        $this->httpFoundationFactory = $httpFoundationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $psr7Request = $this->httpMessageFactory->createRequest($request);

        $psr7Response = $this->psrMiddleware->process($psr7Request, $this);

        return $this->httpFoundationFactory->createResponse($psr7Response);
    }

    /**
     * {@inheritdoc}
     */
    public function process(RequestInterface $request)
    {
        $httpFoundationRequest = $this->httpFoundationFactory->createRequest($request);

        $httpFoundationResponse = $this->next->handle($httpFoundationRequest);

        return $this->httpMessageFactory->createResponse($httpFoundationResponse);
    }
}

