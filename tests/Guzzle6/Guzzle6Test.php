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

namespace Teknoo\Tests\Jenkins\Guzzle6;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Teknoo\Jenkins\Transport\Guzzle6\Guzzle6;
use Teknoo\Jenkins\Transport\PromiseInterface;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @covers \Teknoo\Jenkins\Transport\Guzzle6\Guzzle6
 */
class Guzzle6Test extends TestCase
{
    private function createTestable(): Guzzle6
    {
        return new Guzzle6(
            $this->createMock(Client::class),
        );
    }

    public function testCreateUri()
    {
        self::assertInstanceOf(
            UriInterface::class,
            $this->createTestable()->createUri('htttps://teknoo.net')
        );
    }

    public function testCreateRequest()
    {
        self::assertInstanceOf(
            RequestInterface::class,
            $this->createTestable()->createRequest(
                'get',
                $this->createMock(UriInterface::class),
            ),
        );
    }

    public function testCreateStream()
    {
        self::assertInstanceOf(
            StreamInterface::class,
            $this->createTestable()->createStream($a = 'foo'),
        );

        self::assertInstanceOf(
            StreamInterface::class,
            $this->createTestable()->createStream($a = ['foo']),
        );
    }

    public function testAsyncExecute()
    {
        self::assertInstanceOf(
            PromiseInterface::class,
            $this->createTestable()->asyncExecute(
                $this->createMock(RequestInterface::class),
            )
        );
    }
}
