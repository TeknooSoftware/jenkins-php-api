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
use Teknoo\Jenkins\Enums\BuildStatus;
use Teknoo\Jenkins\Jenkins;

use function ceil;
use function max;
use function property_exists;
use function time;

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
class Build
{
    /**
     * @var callable
     */
    private $getExecutors;

    public function __construct(
        private stdClass $build,
        callable $getExecutors,
    ) {
        $this->getExecutors = $getExecutors;
    }

    /**
     * @return array<string, string|int|bool>
     */
    public function getInputParameters(): array
    {
        $parameters = [];

        if (!property_exists($this->build->actions[0], 'parameters')) {
            return $parameters;
        }

        foreach ($this->build->actions[0]->parameters as $parameter) {
            $parameters[$parameter->name] = $parameter->value ?? null;
        }

        return $parameters;
    }

    public function getTimestamp(): int
    {
        //division par 1000 => pas de millisecondes
        return (int) ($this->build->timestamp / 1000);
    }


    public function getDuration(): int
    {
        //division par 1000 => pas de millisecondes
        return (int) ($this->build->duration / 1000);
    }

    public function getNumber(): int
    {
        return $this->build->number;
    }

    public function getProgress(): ?int
    {
        return $this->getExecutor()?->getProgress();
    }

    public function getEstimatedDuration(): ?float
    {
        //since version 1.461 estimatedDuration is displayed in jenkins's api
        //we can use it witch is more accurate than calculate ourselves
        //but older versions need to continue to work, so in case of estimated
        //duration is not found we fallback to calculate it.
        if (property_exists($this->build, 'estimatedDuration')) {
            return $this->build->estimatedDuration / 1000;
        }

        $duration = null;
        $progress = $this->getProgress();
        if (null !== $progress && $progress >= 0) {
            $duration = ceil((time() - $this->getTimestamp()) / ($progress / 100));
        }

        return $duration;
    }


    /**
     * Returns remaining execution time (seconds)
     */
    public function getRemainingExecutionTime(): ?int
    {
        if (null === ($estimatedDuration = $this->getEstimatedDuration())) {
            return null;
        }

        //be carefull because time from JK server could be different
        //of time from Jenkins server
        //but i didn't find a timestamp given by Jenkins api

        $remaining = (int) $estimatedDuration - (time() - $this->getTimestamp());
        return max(0, $remaining);
    }

    public function getResult(): ?BuildStatus
    {
        return BuildStatus::tryFrom($this->build->result);
    }

    public function getUrl(): string
    {
        return $this->build->url;
    }

    public function getExecutor(): ?Executor
    {
        if (!$this->isRunning()) {
            return null;
        }

        $runExecutor = null;
        foreach (($this->getExecutors)() as $executor) {
            /** @var Executor $executor */

            if ($this->getUrl() === $executor->getBuildUrl()) {
                $runExecutor = $executor;
            }
        }

        return $runExecutor;
    }

    public function isRunning(): bool
    {
        return BuildStatus::RUNNING === $this->getResult();
    }

    public function getBuiltOn(): string
    {
        return $this->build->builtOn;
    }
}
