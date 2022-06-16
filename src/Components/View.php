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
        private \stdClass $view,
        private Jenkins $jenkins
    ) {
    }

    public function getName(): string
    {
        return $this->view->name;
    }

    public function getDescription(): string
    {
        return (isset($this->view->description)) ? $this->view->description : null;
    }

    public function getURL(): string
    {
        return (isset($this->view->url)) ? $this->view->url : null;
    }

    public function getJobs(): array
    {
        $jobs = array();

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

    protected function getColorPriority(string $color): int
    {
        switch ($color) {
            default:
                return 999;
            case 'red_anime':
                return 11;
            case 'red':
                return 10;
            case 'yellow_anime':
                return 6;
            case 'yellow':
                return 5;
            case 'blue_anime':
                return 2;
            case 'blue':
                return 1;
            case 'disabled':
                return 0;
        }
    }
}
