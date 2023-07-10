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

namespace Teknoo\Tests\Jenkins\Components;

use PHPUnit\Framework\TestCase;
use Teknoo\Jenkins\Components\Computer;
use Teknoo\Jenkins\Components\Executor;
use Teknoo\Jenkins\Jenkins;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @covers \Teknoo\Jenkins\Components\Executor
 */
class ExecutorTest extends TestCase
{
    private function createTestable(?array $def = null): Executor
    {
        $definitions = json_decode(
            json_encode(
                $def ?? [

            ]
            ),
            true
        );

        $jenkins = $this->createMock(Jenkins::class);
        $computer = $this->createMock(Computer::class),

        return new Executor(
            executor: $definitions,
            computer: $computer,
            jenkins: $jenkins,
        )
    }
}
