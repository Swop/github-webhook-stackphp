<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <swop@swop.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swop\Stack;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GitHubWebHook implements HttpKernelInterface
{
    /** @var HttpKernelInterface */
    private $kernel;
    /** @var string */
    private $gitHubWebHookSecret;

    /**
     * @param HttpKernelInterface $kernel              Application kernel
     * @param string              $gitHubWebHookSecret GitHub secret key configured in the WebHook
     */
    public function __construct(HttpKernelInterface $kernel, $gitHubWebHookSecret)
    {
        $this->kernel              = $kernel;
        $this->gitHubWebHookSecret = $gitHubWebHookSecret;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if (!$this->isSignatureValid($request)) {
            return new JsonResponse(
                array('error' => Response::HTTP_UNAUTHORIZED, 'message' => 'Unauthorized'),
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $this->kernel->handle($request, $type, $catch);
    }

    private function isSignatureValid(Request $request)
    {
        $hubSignature = $request->headers->get('X-Hub-Signature');
        $explodeResult = explode('=', $hubSignature, 2);

        if (2 !== count($explodeResult)) {
            return false;
        }

        list($algorithm, $hash) = $explodeResult;
        $payload = $request->getContent();

        $payloadHash = hash_hmac($algorithm, $payload, $this->gitHubWebHookSecret);

        return $hash === $payloadHash;
    }
}
