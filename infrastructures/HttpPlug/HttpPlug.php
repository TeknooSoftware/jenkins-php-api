<?php

/*
 * Jenkins API Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\Jenkins\HttpPlug\Transport;

use Http\Client\HttpAsyncClient;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use Http\Message\RequestFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Teknoo\Jenkins\Transport\PromiseInterface;
use Teknoo\Jenkins\Transport\TransportInterface;

use function is_string;
use function preg_match;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HttpPlug implements TransportInterface
{
    private HttpAsyncClient $client;

    private UriFactory $uriFactory;

    private RequestFactory $requestFactory;

    private StreamFactory $streamFactory;

    public function __construct(
        HttpAsyncClient $client,
        UriFactory $uriFactory,
        RequestFactory $requestFactory,
        StreamFactory $streamFactory
    ) {
        $this->client = $client;
        $this->uriFactory = $uriFactory;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return $this->uriFactory->createUri($uri);
    }

    /**
     * @param UriInterface|string $uri
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        $request = $this->requestFactory->createRequest($method, $uri);
        $boundary = uniqid('', true);

        return $request->withHeader('Content-Type', 'multipart/form-data; boundary="' . $boundary . '"');
    }

    /**
     * @param string|array<string, array<string, string>> $elements
     */
    public function createStream(string|array &$elements, ?RequestInterface $request = null): StreamInterface
    {
        if (!$request instanceof RequestInterface) {
            throw new RuntimeException('Error, missing request, needed to cretate a Multipart Stream');
        }

        if (is_string($elements)) {
            return $this->streamFactory->createStream($elements);
        }

        $builder = new MultipartStreamBuilder($this->streamFactory);
        foreach ($elements as &$value) {
            if (isset($value['name'], $value['contents'])) {
                $builder->addResource($value['name'], $value['contents']);
            }
        }

        $contentType = $request->getHeader('Content-Type');
        $boundary = [];
        preg_match('#multipart/form-data; boundary="([^"]+)"#iS', $contentType[0], $boundary);
        $builder->setBoundary($boundary[1]);

        return $builder->build();
    }

    public function asyncExecute(RequestInterface $request): PromiseInterface
    {
        $promise = $this->client->sendAsyncRequest($request);

        return new HttpPlugPromise($promise);
    }
}
