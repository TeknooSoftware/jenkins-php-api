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

use DOMDocument;
use Teknoo\Jenkins\Jenkins;

use function property_exists;

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
class Job
{
    public function __construct(
        private \stdClass $job,
        private Jenkins $jenkins
    ) {
    }

    /**
     * @return Build[]
     */
    public function getBuilds(): array
    {
        $builds = array();
        foreach ($this->job->builds as $build) {
            $builds[] = $this->getJenkinsBuild($build->number);
        }

        return $builds;
    }


    /**
     * @throws \RuntimeException
     */
    public function getJenkinsBuild(int $buildId): Build
    {
        return $this->jenkins->getBuild($this->getName(), $buildId);
    }

    public function getName(): string
    {
        return $this->job->name;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getParametersDefinition(): array
    {
        $parameters = array();

        foreach ($this->job->actions as $action) {
            if (!property_exists($action, 'parameterDefinitions')) {
                continue;
            }

            foreach ($action->parameterDefinitions as $parameterDefinition) {
                $default = $parameterDefinition?->defaultParameterValue?->value;
                $description = $parameterDefinition?->description;
                $choices = $parameterDefinition?->choices;

                $parameters[(string) $parameterDefinition->name] = array(
                    'default'     => $default,
                    'choices'     => $choices,
                    'description' => $description,
                );
            }
        }

        return $parameters;
    }

    public function getColor(): string
    {
        return $this->job->color;
    }

    public function retrieveXmlConfigAsString(): string
    {
        return $this->jenkins->getJobConfig($this->getName());
    }

    public function getLastSuccessfulBuild(): ?Build
    {
        if (null === $this->job->lastSuccessfulBuild) {
            return null;
        }

        return $this->jenkins->getBuild($this->getName(), $this->job->lastSuccessfulBuild->number);
    }

    public function getLastBuild(): ?Build
    {
        if (null === $this->job->lastBuild) {
            return null;
        }

        return $this->jenkins->getBuild($this->getName(), $this->job->lastBuild->number);
    }
}
