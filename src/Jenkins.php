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

namespace Teknoo\Jenkins;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use stdClass;
use Teknoo\Jenkins\Components\Computer;
use Teknoo\Jenkins\Components\Executor;
use Teknoo\Jenkins\Components\Job;
use Teknoo\Jenkins\Transport\TransportInterface;
use Teknoo\Jenkins\Transport\PromiseInterface;
use Teknoo\Jenkins\Transport\Request;

use Throwable;
use function array_keys;
use function json_decode;

use function rawurlencode;
use const JSON_THROW_ON_ERROR;

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
class Jenkins
{
    private ?stdClass $jenkins = null;

    /*
     * Whether or not to retrieve and send anti-CSRF crumb tokens
     * with each request
     *
     * Defaults to false for backwards compatibility
     */
    private bool $hasCrumbsEnabled = false;

    /*
     * The anti-CSRF crumb to use for each request
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     */
    private ?string $crumbValue = null;

    /*
     * The header to use for sending anti-CSRF crumbs
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     */
    private ?string $crumbRequestField = null;

    /**
     * Create a new instance of the Jenkins management interface class.
     * 
     * Uses the provided parameters to formulate the URL used for API requests.
     */
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $token,
        private readonly bool $useHttps = true,
    ) {
        if(empty($this->host)) {
            throw new InvalidArgumentException("Unable to create new Jenkins management class; Invalid DNS hostname or IP address provided.");
        }

        if($this->port < 1 || $this->port > 65535) {
            throw new InvalidArgumentException("Unable to create new Jenkins management class; Invalid port provided.");
        }

        if(empty($this->username)) {
            throw new InvalidArgumentException("Unable to create new Jenkins management class; Invalid username provided.");
        }

        if(empty($this->token)) {
            throw new InvalidArgumentException("Unable to create new Jenkins management class; Invalid token provided.");
        }
    }

    private function getScheme(): string
    {
        if (false === $this->useHttps) {
            return 'http://';
        }

        return 'https://';
    }

    private static function addHeadersToRequest(
        Request $jenkinsRequest,
        RequestInterface $httpRequest
    ): RequestInterface {
        foreach ($jenkinsRequest->getHeaders() as $name => $value) {
            $httpRequest = $httpRequest->withAddedHeader($name, $value);
        }

        return $httpRequest;
    }

    private function executeGetQuery(
        Request $jenkinsRequest,
    ): PromiseInterface {
        $httpRequest = $this->transport->createRequest(
            method: 'get',
            uri: $this->getScheme() . $this->host . $jenkinsRequest->getPath(),
        );

        $httpRequest = self::addHeadersToRequest($jenkinsRequest, $httpRequest);

        return $this->transport->asyncExecute($httpRequest);
    }

    private function setBodyRequest(RequestInterface $httpRequest, Request $jenkinsRequest): RequestInterface
    {
        $multipartBody = [];
        foreach ($jenkinsRequest->getFields() as $key => $value) {
            $multipartBody[] = [
                'name' => $key,
                'contents' => $value
            ];
        }

        $stream = $this->transport->createStream($multipartBody, $httpRequest);

        return $httpRequest->withBody($stream);
    }

    public function executePostQuery(
        Request $jenkinsRequest,
    ): PromiseInterface {
        $httpRequest = $this->transport->createRequest(
            method: 'get',
            uri: $this->getScheme() . $this->host . $jenkinsRequest->getPath(),
        );

        $httpRequest = self::addHeadersToRequest($jenkinsRequest, $httpRequest);

        $httpRequest = $this->setBodyRequest($httpRequest, $jenkinsRequest);

        return $this->transport->asyncExecute($httpRequest);
    }

    /*
     * Enable the use of anti-CSRF crumbs on requests
     */
    public function enableCrumbs(): void
    {
        $this->hasCrumbsEnabled = true;

        $this->requestCrumb();
    }

    /**
     * @throws \JsonException
     */
    private static function jsonDecode(string $response): \stdClass
    {
        return json_decode(
            json: $response,
            associative: false,
            flags: JSON_THROW_ON_ERROR
        );
    }

    private function requestCrumb(): void
    {
        $this->executeGetQuery(
            new Request(
                path: '/crumbIssuer/api/json',
                username: $this->username,
                token: $this->token,
            ),
        )->then(
            self::jsonDecode(...),
            fn (Throwable $error) => throw new RuntimeException(
                message: 'Invalid Crumb response',
                code: $error->getCode(),
                previous: $error,
            ),
        )->then(
            function ($crumbResult): void {
                $this->crumbValue = $crumbResult?->crumb;
                $this->crumbRequestField = $crumbResult?->crumbRequestField;
                $this->hasCrumbsEnabled = !empty($this->crumbValue) && !empty($this->crumbRequestField);
            }
        )->wait();
    }

    /*
     * Disable the use of anti-CSRF crumbs on requests
     */
    public function disableCrumbs(): void
    {
        $this->hasCrumbsEnabled = false;
    }

    /*
     * Get the status of anti-CSRF crumbs
     */
    public function areCrumbsEnabled(): bool
    {
        return $this->hasCrumbsEnabled;
    }

    private function getCrumbHeader(): array
    {
        if (true === $this->hasCrumbsEnabled) {
            return [$this->crumbRequestField] = $this->crumbValue;
        }

        return [];
    }

    public function isAvailable(): bool
    {
        try {
            $this->executeGetQuery(
                new Request(
                    path: '/crumbIssuer/api/json',
                    username: $this->username,
                    token: $this->token,
                    headers: $this->getCrumbHeader(),
                ),
            )->then(
                fn() => $this->getQueue(),
                fn (Throwable $error) => throw new RuntimeException(
                    message: 'Not Available',
                    code: $error->getCode(),
                    previous: $error,
                ),
            )->wait();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function initialize(): void
    {
        if (null !== $this->jenkins) {
            return;
        }

        $this->executeGetQuery(
            new Request(
                path: '/api/json',
                username: $this->username,
                token: $this->token,
                headers: $this->getCrumbHeader(),
            ),
        )->then(
            self::jsonDecode(...),
            fn (Throwable $error) => throw new RuntimeException(
                message: 'Invalid response',
                code: $error->getCode(),
                previous: $error,
            ),
        )->then(
            fn (stdClass $result) => $this->jenkins,
        )->wait();
    }

    public function getAllJobs(): array
    {
        $this->initialize();

        $jobs = [];
        foreach ($this->jenkins->jobs as $job) {
            $jobs[$job->name] = true;
        }

        return array_keys($jobs);
    }

    public function getJobs(): array
    {
        $this->initialize();

        $jobs = array();
        foreach ($this->jenkins->jobs as $job) {
            $jobs[$job->name] = $this->getJob($job->name);
        }

        return $jobs;
    }

    public function getExecutors(Computer $computer): array
    {
        $this->initialize();

        $executors = [];
        $numExecutors = ($this->jenkins?->numExecutors ?? 1);
        $computerName = rawurlencode($computer->getName());
        for ($i = 0; $i < $numExecutors; $i++) {
            $this->executeGetQuery(
                new Request(
                    path: "/computer/$computerName/executors/$i/api/json",
                    username: $this->username,
                    token: $this->token,
                    headers: $this->getCrumbHeader(),
                ),
            )->then(
                self::jsonDecode(...),
                fn (Throwable $error) => throw new RuntimeException(
                    message: 'Invalid executor response',
                    code: $error->getCode(),
                    previous: $error,
                ),
            )->then(
                function ($infos) use (&$executors, $computer): void {
                    $executors[] = new Executor($infos, $computer, $this);
                },
            )->wait();
        }

        return $executors;
    }

    public function launchJob(string $jobName, array $parameters = []): self
    {
        $jobName = rawurlencode($jobName);
        if (empty($parameters)) {
            $path = "/job/$jobName/build";
        } else {
            $path = "/job/$jobName/buildWithParameters";
        }

        $this->executePostQuery(
            new Request(
                path: $path,
                username: $this->username,
                token: $this->token,
                headers: $this->getCrumbHeader(),
                fields: $parameters,
            ),
        )->then(
            self::jsonDecode(...),
            fn (Throwable $error) => throw new RuntimeException(
                message: 'Invalid start job response',
                code: $error->getCode(),
                previous: $error,
            ),
        )->wait();

        return $this;
    }

    public function getJob(string $jobName): Job
    {
        $jobName = rawurlencode($jobName);

        return $this->executeGetQuery(
            new Request(
                path: $path = "/job/$jobName/api/json",
                username: $this->username,
                token: $this->token,
                headers: $this->getCrumbHeader(),
            ),
        )->then(
            static function (string $response): string {
                //todo manage 404
                return $response;
            },
            fn (Throwable $error) => throw new RuntimeException(
                message: 'Invalid job response',
                code: $error->getCode(),
                previous: $error,
            ),
        )->then(
            self::jsonDecode(...),
        )->then(
            fn (stdClass $infos) => new Job($infos, $this),
        )->wait();
    }

    public function deleteJob(string $jobName): void
    {
        $jobName = rawurlencode($jobName);

        $this->executeGetQuery(
            new Request(
                path: $path = "/job/$jobName/doDelete",
                username: $this->username,
                token: $this->token,
                headers: $this->getCrumbHeader(),
            ),
        )->otherwise(
            fn (Throwable $error) => throw new RuntimeException(
                message: 'Invalid delete job response',
                code: $error->getCode(),
                previous: $error,
            ),
        );
    }

    public function getQueue(): Queue
    {
        $url  = sprintf('%s/queue/api/json', $this->baseUrl);
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error during getting information for queue on %s', $this->baseUrl));

        $infos = json_decode($ret);
        if (!$infos instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }

        return new Jenkins\Queue($infos, $this);
    }

    /**
     * @return Jenkins\View[]
     */
    public function getViews(): array
    {
        $this->initialize();

        $views = array();
        foreach ($this->jenkins->views as $view) {
            $views[] = $this->getView($view->name);
        }

        return $views;
    }

    public function getPrimaryView(): ?View
    {
        $this->initialize();
        $primaryView = null;

        if (property_exists($this->jenkins, 'primaryView')) {
            $primaryView = $this->getView($this->jenkins->primaryView->name);
        }

        return $primaryView;
    }


    public function getView(string $viewName): View
    {
        $url  = sprintf('%s/view/%s/api/json', $this->baseUrl, rawurlencode($viewName));
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl(
            $curl,
            sprintf('Error during getting information for view %s on %s', $viewName, $this->baseUrl)
        );

        $infos = json_decode($ret);
        if (!$infos instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }

        return new Jenkins\View($infos, $this);
    }


    /**
     * @param        $job
     * @param        $buildId
     * @param string $tree
     *
     * @return Jenkins\Build
     * @throws RuntimeException
     */
    public function getBuild($job, $buildId, $tree = 'actions[parameters,parameters[name,value]],result,duration,timestamp,number,url,estimatedDuration,builtOn')
    {
        if ($tree !== null) {
            $tree = sprintf('?tree=%s', $tree);
        }
        $url  = sprintf('%s/job/%s/%d/api/json%s', $this->baseUrl, rawurlencode($job), $buildId, $tree);
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl(
            $curl,
            sprintf('Error during getting information for build %s#%d on %s', $job, $buildId, $this->baseUrl)
        );

        $infos = json_decode($ret);

        if (!$infos instanceof stdClass) {
            return null;
        }

        return new Jenkins\Build($infos, $this);
    }

    /**
     * @param string $job
     * @param int    $buildId
     *
     * @return null|string
     */
    public function getUrlBuild($job, $buildId)
    {
        return (null === $buildId) ?
            $this->getUrlJob($job)
            : sprintf('%s/job/%s/%d', $this->baseUrl, rawurlencode($job), $buildId);
    }

    /**
     * @param string $computerName
     *
     * @return Jenkins\Computer
     * @throws RuntimeException
     */
    public function getComputer($computerName)
    {
        $url  = sprintf('%s/computer/%s/api/json', $this->baseUrl, $computerName);
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl(
            $curl,
            sprintf('Error during getting information for computer %s on %s', $computerName, $this->baseUrl)
        );

        $infos = json_decode($ret);

        if (!$infos instanceof stdClass) {
            return null;
        }

        return new Jenkins\Computer($infos, $this);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $job
     *
     * @return string
     */
    public function getUrlJob($job)
    {
        return sprintf('%s/job/%s', $this->baseUrl, rawurlencode($job));
    }

    /**
     * getUrlView
     *
     * @param string $view
     *
     * @return string
     */
    public function getUrlView($view)
    {
        return sprintf('%s/view/%s', $this->baseUrl, $view);
    }

    /**
     * @param string $jobname
     *
     * @return string
     *
     * @deprecated use getJobConfig instead
     *
     * @throws RuntimeException
     */
    public function retrieveXmlConfigAsString($jobname)
    {
        return $this->getJobConfig($jobname);
    }

    /**
     * @param string       $jobname
     * @param \DomDocument $document
     *
     * @deprecated use setJobConfig instead
     */
    public function setConfigFromDomDocument($jobname, \DomDocument $document)
    {
        $this->setJobConfig($jobname, $document->saveXML());
    }

    /**
     * @param string $jobname
     * @param string $xmlConfiguration
     *
     * @throws InvalidArgumentException
     */
    public function createJob($jobname, $xmlConfiguration)
    {
        $url  = sprintf('%s/createItem?name=%s', $this->baseUrl, rawurlencode($jobname));
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_POST, 1);

        curl_setopt($curl, \CURLOPT_POSTFIELDS, $xmlConfiguration);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);

        $headers = array('Content-Type: text/xml');

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);

        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) {
            throw new InvalidArgumentException(sprintf('Job %s already exists', $jobname));
        }
        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error creating job %s', $jobname));
        }
    }

    /**
     * @param string $jobname
     * @param        $configuration
     *
     * @internal param string $document
     */
    public function setJobConfig($jobname, $configuration)
    {
        $url  = sprintf('%s/job/%s/config.xml', $this->baseUrl, rawurlencode($jobname));
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_POST, 1);
        curl_setopt($curl, \CURLOPT_POSTFIELDS, $configuration);

        $headers = array('Content-Type: text/xml');

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
        curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error during setting configuration for job %s', $jobname));
    }

    /**
     * @param string $jobname
     *
     * @return string
     */
    public function getJobConfig($jobname)
    {
        $url  = sprintf('%s/job/%s/config.xml', $this->baseUrl, rawurlencode($jobname));
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error during getting configuration for job %s', $jobname));

        return $ret;
    }

    /**
     * @param Jenkins\Executor $executor
     *
     * @throws RuntimeException
     */
    public function stopExecutor(Jenkins\Executor $executor)
    {
        $url = sprintf(
            '%s/computer/%s/executors/%s/stop', $this->baseUrl, $executor->getComputer(), $executor->getNumber()
        );

        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
        curl_exec($curl);

        $this->validateCurl(
            $curl,
            sprintf('Error during stopping executor #%s', $executor->getNumber())
        );
    }

    /**
     * @param Jenkins\JobQueue $queue
     *
     * @throws RuntimeException
     * @return void
     */
    public function cancelQueue(Jenkins\JobQueue $queue)
    {
        $url = sprintf('%s/queue/item/%s/cancelQueue', $this->baseUrl, $queue->getId());

        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
        curl_exec($curl);

        $this->validateCurl(
            $curl,
            sprintf('Error during stopping job queue #%s', $queue->getId())
        );

    }

    /**
     * @param string $computerName
     *
     * @throws RuntimeException
     * @return void
     */
    public function toggleOfflineComputer($computerName)
    {
        $url  = sprintf('%s/computer/%s/toggleOffline', $this->baseUrl, $computerName);
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
        curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error marking %s offline', $computerName));
    }

    /**
     * @param string $computerName
     *
     * @throws RuntimeException
     * @return void
     */
    public function deleteComputer($computerName)
    {
        $url  = sprintf('%s/computer/%s/doDelete', $this->baseUrl, $computerName);
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
        curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error deleting %s', $computerName));
    }

    /**
     * @param string $jobname
     * @param string $buildNumber
     *
     * @return string
     */
    public function getConsoleTextBuild($jobname, $buildNumber)
    {
        $url  = sprintf('%s/job/%s/%s/consoleText', $this->baseUrl, rawurlencode($jobname), $buildNumber);
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);

        return curl_exec($curl);
    }

    public function getTestReport($jobName, $buildId): array
    {
        $url  = sprintf('%s/job/%s/%d/testReport/api/json', $this->baseUrl, rawurlencode($jobName), $buildId);
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $errorMessage = sprintf(
            'Error during getting information for build %s#%d on %s', $jobName, $buildId, $this->baseUrl
        );

        $this->validateCurl(
            $curl,
            $errorMessage
        );

        $infos = json_decode($ret);

        if (!$infos instanceof stdClass) {
            throw new RuntimeException($errorMessage);
        }

        return new Jenkins\TestReport($this, $infos, $jobName, $buildId);
    }

    /**
     * Returns the content of a page according to the jenkins base url.
     * Useful if you use jenkins plugins that provides specific APIs.
     * (e.g. "/cloud/ec2-us-east-1/provision")
     *
     * @param string $uri
     * @param array  $curlOptions
     *
     * @return string
     */
    private function execute($uri, array $curlOptions): string
    {
        $url  = $this->baseUrl . '/' . $uri;
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOptions);
        $ret = curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error calling "%s"', $url));

        return $ret;
    }

    /**
     * @return Jenkins\Computer[]
     */
    public function getComputers(): array
    {
        $return = $this->execute(
            '/computer/api/json', array(
                \CURLOPT_RETURNTRANSFER => 1,
            )
        );
        $infos  = json_decode($return);
        if (!$infos instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }
        $computers = array();
        foreach ($infos->computer as $computer) {
            $computers[] = $this->getComputer($computer->displayName);
        }

        return $computers;
    }

    /**
     * @param string $computerName
     *
     * @return string
     */
    public function getComputerConfiguration($computerName): string
    {
        return $this->execute(sprintf('/computer/%s/config.xml', $computerName), array(\CURLOPT_RETURNTRANSFER => 1,));
    }

    /**
     * Validate curl_error() and http_code in a cURL request
     *
     * @param $curl
     * @param $errorMessage
     */
    private function validateCurl($curl, $errorMessage) {

        if (curl_errno($curl)) {
            throw new RuntimeException($errorMessage);
        }
        $info = curl_getinfo($curl);

        if ($info['http_code'] === 403) {
            throw new RuntimeException(sprintf('Access Denied [HTTP status code 403] to %s"', $info['url']));
        }
    }
}
