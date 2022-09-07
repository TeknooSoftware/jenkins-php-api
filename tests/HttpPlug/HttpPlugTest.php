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

namespace Teknoo\Tests\Jenkins\HttpPlug;

use Http\Client\HttpAsyncClient;
use Http\Message\RequestFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use PHPUnit\Framework\TestCase;
use Teknoo\Jenkins\Transport\HttpPlug\HttpPlug;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @covers \Teknoo\Jenkins\Transport\HttpPlug\HttpPlug
 */
class HttpPlugTest extends TestCase
{
    private function createTestable(): HttpPlug
    {
        return new HttpPlug(
            $this->createMock(HttpAsyncClient::class),
            $this->createMock(UriFactory::class),
            $this->createMock(RequestFactory::class),
            $this->createMock(StreamFactory::class),
        );
    }

    public function testCreateUri()
    {

    }

    public function testCreateRequest()
    {

    }

    public function testCreateStream()
    {

    }

    public function testAsyncExecute()
    {

    }
}
