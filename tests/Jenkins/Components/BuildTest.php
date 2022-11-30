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

namespace Teknoo\Tests\Jenkins\Components;

use PHPUnit\Framework\TestCase;
use Teknoo\Jenkins\Components\Build;
use Teknoo\Jenkins\Components\Executor;
use Teknoo\Jenkins\Enums\BuildStatus;
use function json_decode;
use function json_encode;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @covers \Teknoo\Jenkins\Components\Build
 */
class BuildTest extends TestCase
{
    private function createTestable(?array $def = null): Build
    {
        $definitions = json_decode(
            json_encode(
                $def ?? [

                ]
            ),
            true
        );

        return new Build(
            $definitions,
            function () {
                yield $this->createMock(Executor::class);
                yield $this->createMock(Executor::class);
            },
        );
    }

    public function testGetInputParameters()
    {
        self::assertIsArray($this->createTestable());
        self::assertEmpty($this->createTestable([]));
    }

    public function testGetTimestamp()
    {
        self::assertIsInt($t = $this->createTestable()->getTimestamp());
        self::assertNotEmpty($t);
    }

    public function testGetDuration()
    {
        self::assertIsInt($t = $this->createTestable()->getDuration());
        self::assertNotEmpty($t);
    }

    public function testGetNumber()
    {
        self::assertIsInt($t = $this->createTestable()->getNumber());
        self::assertNotEmpty($t);
    }

    public function testGetProgress()
    {
        self::assertIsInt($t = $this->createTestable()->getProgress());
        self::assertNotEmpty($t);
        self::assertNull($this->createTestable([])->getProgress());
    }

    public function testGetEstimatedDuration()
    {
        self::assertIsFloat($t = $this->createTestable()->getEstimatedDuration());
        self::assertNotEmpty($t);
        self::assertNull($this->createTestable([])->getEstimatedDuration());
    }

    public function testGetRemainingExecutionTime()
    {
        self::assertIsInt($t = $this->createTestable()->getRemainingExecutionTime());
        self::assertNotEmpty($t);
        self::assertNull($this->createTestable([])->getRemainingExecutionTime());
    }

    public function testGetResult()
    {
        self::assertInstanceOf(BuildStatus::class, $this->createTestable()->getResult());
        self::assertNull($this->createTestable([])->getResult());
    }

    public function testGetUrl()
    {
        self::assertIsString($this->createTestable()->getUrl());
        self::assertIsString($this->createTestable([])->getUrl());
    }

    public function testGetExecutor()
    {
        self::assertInstanceOf(Executor::class, $this->createTestable()->getExecutor());
        self::assertNull($this->createTestable([])->getExecutor());
    }

    public function testIsRunning()
    {
        self::assertTrue($this->createTestable()->isRunning());
        self::assertFalse($this->createTestable([])->isRunning());
    }

    public function testGetBuiltOn()
    {
        self::assertIsString($this->createTestable()->getBuiltOn());
        self::assertIsString($this->createTestable([])->getBuiltOn());
    }
}
