<?php

/*
 * Jenkins API Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\Jenkins\Transport\Guzzle6;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Teknoo\Jenkins\Transport\PromiseInterface;
use Teknoo\Jenkins\Transport\TransportInterface;

use function fopen;
use function fwrite;
use function is_string;
use function rewind;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Guzzle6 implements TransportInterface
{
    /**
     * Guzzle instance.
     */
    private Client $guzzleClient;

    public function __construct(Client $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    /**
     * @param UriInterface|string $uri
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    public function createStream(string|array &$elements, ?RequestInterface $request = null): StreamInterface
    {
        if (is_string($elements)) {
            $stream = fopen('php://memory', 'b+');
            if (false === $stream) {
                throw new RuntimeException("String stream not opeenable to send a HTTP Request");
            }

            fwrite($stream, $elements);
            rewind($stream);

            return new Stream($stream);
        }

        return new MultipartStream($elements);
    }

    public function asyncExecute(RequestInterface $request): PromiseInterface
    {
        $guzzlePromise = $this->guzzleClient->sendAsync($request);

        return new Guzzle6Promise($guzzlePromise);
    }
}
