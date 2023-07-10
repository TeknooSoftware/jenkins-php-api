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
 * @copyright   Copyright (c) Matthew Wendel (https://github.com/ooobii) [author of v1.x]
 * @copyright   Copyright (c) jenkins-khan (https://github.com/jenkins-khan/jenkins-php-api) [author of v1.x]
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\Jenkins\Components;

use stdClass;
use Teknoo\Jenkins\Jenkins;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Matthew Wendel (https://github.com/ooobii) [author of v1.x]
 * @copyright   Copyright (c) jenkins-khan (https://github.com/jenkins-khan/jenkins-php-api) [author of v1.x]
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Computer
{
    public function __construct(
        private readonly stdClass $computer,
        private readonly Jenkins $jenkins
    ) {
    }

    public function getName(): string
    {
        return (string) $this->computer?->displayName;
    }

    public function isOffline(): bool
    {
        return (bool) $this->computer->offline;
    }

    /**
     *
     * returns null when computer is launching
     * returns \stdClass when computer has been put offline
     */
    public function getOfflineCause(): ?stdClass
    {
        return $this->computer->offlineCause;
    }

    public function toggleOffline(): self
    {
        $this->jenkins->toggleOfflineComputer($this->getName());

        return $this;
    }

    public function delete(): self
    {
        $this->jenkins->deleteComputer($this->getName());

        return $this;
    }

    public function getConfiguration(): string
    {
        return $this->jenkins->getComputerConfiguration($this->getName());
    }
}
