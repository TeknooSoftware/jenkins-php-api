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
class View
{
    public function __construct(
        private readonly stdClass $view,
        private readonly Jenkins $jenkins
    ) {
    }

    public function getName(): string
    {
        return $this->view->name;
    }

    public function getDescription(): string
    {
        return $this->view?->description ?? '';
    }

    public function getURL(): string
    {
        return $this->view?->url ?? '';
    }

    /**
     * @return Job[]
     */
    public function getJobs(): array
    {
        $jobs = [];

        foreach ($this->view->jobs as $job) {
            $jobs[] = $this->jenkins->getJob($job->name);
        }

        return $jobs;
    }

    public function getColor(): string
    {
        $color = 'blue';
        foreach ($this->view->jobs as $job) {
            if ($this->getColorPriority($job->color) > $this->getColorPriority($color)) {
                $color = $job->color;
            }
        }

        return $color;
    }

    private function getColorPriority(string $color): int
    {
        return match ($color) {
            'red_anime' => 11,
            'red' => 10,
            'yellow_anime' => 6,
            'yellow' => 5,
            'blue_anime' => 2,
            'blue' => 1,
            'disabled' => 0,
            default => 999,
        };
    }
}
