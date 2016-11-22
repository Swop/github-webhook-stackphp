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

use Swop\GitHubWebHook\Security\SignatureValidator;
use Swop\GitHubWebHookMiddleware\GithubWebHook as GithubWebHookMiddleware;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class offers a StackPHP middleware which could be used to verify that a request coming from GitHub
 * in a web hook context contains proper signature based on the provided secret.
 *
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubWebHook implements HttpKernelInterface
{
    /** @var HttpKernelInterface */
    private $next;

    /**
     * @param HttpKernelInterface $next   Next middleware
     * @param string              $secret GitHub web hook secret
     */
    public function __construct(HttpKernelInterface $next, $secret)
    {
        $this->next = $this->createBridge($next, $secret);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        return $this->next->handle($request, $type, $catch);
    }
//
//    /**
//     * @param HttpKernelInterface $next   Next middleware
//     * @param string              $secret GitHub web hook secret
//     *
//     * @return $this
//     */
//    static public function create(HttpKernelInterface $next, $secret)
//    {
//        return new self($next, $secret);
//    }

    /**
     * @param HttpKernelInterface $next
     * @param string              $secret
     *
     * @return HttpKernelInterface
     */
    private function createBridge(HttpKernelInterface $next, $secret)
    {
        return new Psr7MiddlewareBridge(
            $next,
            new GithubWebHookMiddleware(new SignatureValidator(), $secret),
            new DiactorosFactory(),
            new HttpFoundationFactory()
        );
    }
}
