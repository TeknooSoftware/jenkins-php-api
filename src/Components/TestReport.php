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
class TestReport
{
    public function __construct(
        private readonly stdClass $testReport,
        private readonly string $jobName,
        private readonly int $buildNumber
    ) {
    }

    public function getOriginalTestReport(): string
    {
        return (string) json_encode($this->testReport, JSON_THROW_ON_ERROR);
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function getBuildNumber(): int
    {
        return $this->buildNumber;
    }

    public function getDuration(): float
    {
        return $this->testReport->duration;
    }

    public function getFailCount(): int
    {
        return $this->testReport->failCount;
    }

    public function getPassCount(): int
    {
        return $this->testReport->passCount;
    }

    public function getSkipCount(): int
    {
        return $this->testReport->skipCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSuites(): array
    {
        return $this->testReport->suites;
    }

    public function getSuite(string|int $id): stdClass
    {
        return $this->testReport->suites[$id];
    }

    public function getSuiteStatus(int $id): string
    {
        $suite  = $this->getSuite($id);
        $status = 'PASSED';
        foreach ($suite->cases as $case) {
            if ('FAILED' === $case->status) {
                $status = 'FAILED';
                break;
            }
        }

        return $status;
    }
}
