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

namespace Teknoo\Tests\Jenkins\Guzzle6;

use GuzzleHttp\Promise\PromiseInterface as GuzzlePromiseInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\Jenkins\Transport\Guzzle6\Guzzle6Promise;
use Teknoo\Jenkins\Transport\PromiseInterface;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @covers \Teknoo\Jenkins\Transport\Guzzle6\Guzzle6Promise
 */
class Guzzle6PromiseTest extends TestCase
{
    private function createTestable(): Guzzle6Promise
    {
        return new Guzzle6Promise(
            $this->createMock(GuzzlePromiseInterface::class)
        );
    }

    public function testThen()
    {
        self::assertInstanceOf(
            PromiseInterface::class,
            $this->createTestable()->then(
                function () {},
                function () {},
            )
        );
    }

    public function testOtherwise()
    {
        self::assertInstanceOf(
            PromiseInterface::class,
            $this->createTestable()->otherwise(
                function () {},
            )
        );
    }

    public function testIsPending()
    {
        self::assertIsBool(
            $this->createTestable()->isPending()
        );
    }

    public function testIsFulfilled()
    {
        self::assertIsBool(
            $this->createTestable()->isFulfilled()
        );
    }

    public function testIsRejected()
    {
        self::assertIsBool(
            $this->createTestable()->isRejected()
        );
    }

    public function testWait()
    {
        self::assertNotEmpty(
            $this->createTestable()->wait()
        );
    }
}
