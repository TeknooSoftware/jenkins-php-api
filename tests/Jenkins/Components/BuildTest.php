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
    private function createTestable(): Build
    {
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

    }

    public function testGetTimestamp()
    {

    }

    public function testGetDuration()
    {

    }

    public function testGetNumber()
    {

    }

    public function testGetProgress()
    {

    }

    public function testGetEstimatedDuration()
    {

    }

    public function testGetRemainingExecutionTime()
    {

    }

    public function testGetResult()
    {

    }

    public function testGetUrl()
    {

    }

    public function testGetExecutor()
    {

    }

    public function testIsRunning()
    {

    }

    public function testGetBuiltOn()
    {

    }
}
