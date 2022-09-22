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
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Matthew Wendel (https://github.com/ooobii) [author of v1.x]
 * @copyright   Copyright (c) jenkins-khan (https://github.com/jenkins-khan/jenkins-php-api) [author of v1.x]
 *
 * @link        http://teknoo.software/jenkins-php-api Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class JobQueue
{
    public function __construct(
        private stdClass $jobQueue,
        private Jenkins $jenkins
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getInputParameters(): array
    {
        $parameters = array();

        if (!\property_exists($this->jobQueue->actions[0], 'parameters')) {
            return $parameters;
        }

        foreach ($this->jobQueue->actions[0]->parameters as $parameter) {
            $parameters[(string) $parameter->name] = $parameter->value;
        }

        return $parameters;
    }

    public function getJobName(): string
    {
        return $this->jobQueue->task->name;
    }

    public function getId(): int
    {
        return $this->jobQueue->id;
    }

    public function cancel(): void
    {
        $this->jenkins->cancelQueue($this);
    }
}
