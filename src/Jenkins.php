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

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var stdClass
     */
    private $jenkins = null;

    /**
     * Whether or not to retrieve and send anti-CSRF crumb tokens
     * with each request
     *
     * Defaults to false for backwards compatibility
     *
     * @var boolean
     */
    private $crumbsEnabled = false;

    /**
     * The anti-CSRF crumb to use for each request
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     *
     * @var string
     */
    private $crumb;

    /**
     * The header to use for sending anti-CSRF crumbs
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     *
     * @var string
     */
    private $crumbRequestField;

    /**
     * Create a new instance of the Jenkins management interface class.
     * 
     * Uses the provided parameters to formulate the URL used for API requests.
     *
     * @param string $host The DNS hostname or TCP/IP address of the Jenkins host to connect to.
     * @param boolean $useHttps Set to `TRUE` if the Jenkins instance you're connecting to does not allow regular HTTP traffic.
     * @param string|null $user *(Required if API needs authentication)* The username to impersonate when connecting to the Jenkins API.
     * @param string|null $token *(Required if API needs authentication)* An API token that belongs to the user to impersonate for Jenkins API communications.
     * @param int $port The TCP/IP port number to send the HTTP/HTTPS requests through.
     *   * If the port number is not specified, the default port number for the selected HTTP/HTTPS transmission method will be used.
     *   * If a port number is specified, the number provided will be included in the constructed API URL.
     * 
     */
    public function __construct($host, $port = NULL, $useHttps = TRUE, $user = NULL, $token = NULL) {

        //make sure host parameter is populated.
        if(!$host || strlen($host) < 1)
            throw new \Exception("Unable to create new Jenkins management class; Invalid DNS hostname or IP address provided.");

        //make sure that, if provided, the port number is a valid value.
        if($port !== NULL && (!is_numeric($port) || $port < 1 || $port > 65535))
            throw new \Exception("Unable to create new Jenkins management class; Invalid port provided.");

        //make sure that, if provided, the username is not empty.
        if($user !== NULL && strlen($user) < 1)
            throw new \Exception("Unable to create new Jenkins management class; Invalid username provided.");

        //make sure that, if provided, the token is not empty.
        if($token !== NULL && empty(trim($token)))
            throw new \Exception("Unable to create new Jenkins management class; Invalid token provided.");


        //use HTTP or HTTPS based on provided argument.
        $baseUrl = "https://";
        $useHttps or $baseUrl = "http://";
    
        //if a username is provided, append it to the URL.
        if($user) $baseUrl .= $user;
    
        //if a token is provided, append it to the URL.
        if($token) $baseUrl .= ':' . trim($token);
    
        //if either a token or username was provided, add the identity separator to the URL.
        if($user || $token) $baseUrl .= '@';
    
        //append the hostname of the Jenkins instance to the URL.
        $baseUrl .= $host;
    
        //if a port was set, append it to the output URL.
        if($port) $baseUrl .= ':' . $port;

        //now that URL is compiled, set the base URL.
        $this->baseUrl = $baseUrl;

    }

    /**
     * Enable the use of anti-CSRF crumbs on requests
     *
     * @return void
     */
    public function enableCrumbs()
    {
        $this->crumbsEnabled = true;

        $crumbResult = $this->requestCrumb();

        if (!$crumbResult || !is_object($crumbResult)) {
            $this->crumbsEnabled = false;

            return;
        }

        $this->crumb             = $crumbResult->crumb;
        $this->crumbRequestField = $crumbResult->crumbRequestField;
    }

    /**
     * Disable the use of anti-CSRF crumbs on requests
     *
     * @return void
     */
    public function disableCrumbs()
    {
        $this->crumbsEnabled = false;
    }

    /**
     * Get the status of anti-CSRF crumbs
     *
     * @return boolean Whether or not crumbs have been enabled
     */
    public function areCrumbsEnabled()
    {
        return $this->crumbsEnabled;
    }

    public function requestCrumb()
    {
        $url = sprintf('%s/crumbIssuer/api/json', $this->baseUrl);

        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);

        $ret = curl_exec($curl);

        $this->validateCurl($curl, 'Error getting csrf crumb');

        $crumbResult = json_decode($ret);

        if (!$crumbResult instanceof \stdClass) {
            throw new \RuntimeException('Error during json_decode of csrf crumb');
        }

        return $crumbResult;
    }

    public function getCrumbHeader()
    {
        return "$this->crumbRequestField: $this->crumb";
    }

    /**
     * @return boolean
     */
    public function isAvailable()
    {
        $curl = curl_init($this->baseUrl . '/api/json');
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        curl_exec($curl);

        if (curl_errno($curl)) {
            return false;
        } else {
            try {
                $this->getQueue();
            } catch (\RuntimeException $e) {
                //en cours de lancement de jenkins, on devrait passer par là
                return false;
            }
        }

        return true;
    }

    /**
     * @return void
     * @throws \RuntimeException
     */
    private function initialize()
    {
        if (null !== $this->jenkins) {
            return;
        }

        $curl = curl_init($this->baseUrl . '/api/json');

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error during getting list of jobs on %s', $this->baseUrl));

        $this->jenkins = json_decode($ret);
        if (!$this->jenkins instanceof \stdClass) {
            throw new \RuntimeException('Error during json_decode');
        }
    }

    /**
     * @throws \RuntimeException
     * @return array
     */
    public function getAllJobs()
    {
        $this->initialize();

        $jobs = array();
        foreach ($this->jenkins->jobs as $job) {
            $jobs[$job->name] = array(
                'name' => $job->name
            );
        }

        return $jobs;
    }

    /**
     * @return Jenkins\Job[]
     */
    public function getJobs()
    {
        $this->initialize();

        $jobs = array();
        foreach ($this->jenkins->jobs as $job) {
            $jobs[$job->name] = $this->getJob($job->name);
        }

        return $jobs;
    }

    /**
     * @param string $computer
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getExecutors($computer = '(master)')
    {
        $this->initialize();

        $executors = array();
        for ($i = 0; $i < $this->jenkins->numExecutors; $i++) {
            $url  = sprintf('%s/computer/%s/executors/%s/api/json', $this->baseUrl, $computer, $i);
            $curl = curl_init($url);

            curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
            $ret = curl_exec($curl);

            $this->validateCurl(
                $curl,
                sprintf( 'Error during getting information for executors[%s@%s] on %s', $i, $computer, $this->baseUrl)
            );

            $infos = json_decode($ret);
            if (!$infos instanceof \stdClass) {
                throw new \RuntimeException('Error during json_decode');
            }

            $executors[] = new Jenkins\Executor($infos, $computer, $this);
        }

        return $executors;
    }

    /**
     * @param       $jobName
     * @param array $parameters
     *
     * @return bool
     * @internal param array $extraParameters
     *
     */
    public function launchJob($jobName, $parameters = array())
    {
        if (0 === count($parameters)) {
            $url = sprintf('%s/job/%s/build', $this->baseUrl, \rawurlencode($jobName));
        } else {
            $url = sprintf('%s/job/%s/buildWithParameters', $this->baseUrl, \rawurlencode($jobName));
        }

        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_POST, 1);
        curl_setopt($curl, \CURLOPT_POSTFIELDS, $parameters);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);

        curl_exec($curl);
        
        $this->validateCurl($curl, sprintf('Error trying to launch job "%s" (%s)', $jobName, $url));

        return true;
    }

    /**
     * @param string $jobName
     *
     * @return bool|\ooobii\Jenkins\Job
     * @throws \RuntimeException
     */
    public function getJob($jobName)
    {
        $url  = sprintf('%s/job/%s/api/json', $this->baseUrl, \rawurlencode($jobName));
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $response_info = curl_getinfo($curl);

        if (200 != $response_info['http_code']) {
            return false;
        }

        $this->validateCurl(
            $curl,
            sprintf('Error during getting information for job %s on %s', $jobName, $this->baseUrl)
        );

        $infos = json_decode($ret);
        if (!$infos instanceof \stdClass) {
            throw new \RuntimeException('Error during json_decode');
        }

        return new Jenkins\Job($infos, $this);
    }

    /**
     * @param string $jobName
     *
     * @return void
     */
    public function deleteJob($jobName)
    {
        $url  = sprintf('%s/job/%s/doDelete', $this->baseUrl, \rawurlencode($jobName));
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, \CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);

        $ret = curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error deleting job %s on %s', $jobName, $this->baseUrl));
    }

    /**
     * @return Jenkins\Queue
     * @throws \RuntimeException
     */
    public function getQueue()
    {
        $url  = sprintf('%s/queue/api/json', $this->baseUrl);
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error during getting information for queue on %s', $this->baseUrl));

        $infos = json_decode($ret);
        if (!$infos instanceof \stdClass) {
            throw new \RuntimeException('Error during json_decode');
        }

        return new Jenkins\Queue($infos, $this);
    }

    /**
     * @return Jenkins\View[]
     */
    public function getViews()
    {
        $this->initialize();

        $views = array();
        foreach ($this->jenkins->views as $view) {
            $views[] = $this->getView($view->name);
        }

        return $views;
    }

    /**
     * @return Jenkins\View|null
     */
    public function getPrimaryView()
    {
        $this->initialize();
        $primaryView = null;

        if (property_exists($this->jenkins, 'primaryView')) {
            $primaryView = $this->getView($this->jenkins->primaryView->name);
        }

        return $primaryView;
    }


    /**
     * @param string $viewName
     *
     * @return Jenkins\View
     * @throws \RuntimeException
     */
    public function getView($viewName)
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
        if (!$infos instanceof \stdClass) {
            throw new \RuntimeException('Error during json_decode');
        }

        return new Jenkins\View($infos, $this);
    }


    /**
     * @param        $job
     * @param        $buildId
     * @param string $tree
     *
     * @return Jenkins\Build
     * @throws \RuntimeException
     */
    public function getBuild($job, $buildId, $tree = 'actions[parameters,parameters[name,value]],result,duration,timestamp,number,url,estimatedDuration,builtOn')
    {
        if ($tree !== null) {
            $tree = sprintf('?tree=%s', $tree);
        }
        $url  = sprintf('%s/job/%s/%d/api/json%s', $this->baseUrl, \rawurlencode($job), $buildId, $tree);
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl(
            $curl,
            sprintf('Error during getting information for build %s#%d on %s', $job, $buildId, $this->baseUrl)
        );

        $infos = json_decode($ret);

        if (!$infos instanceof \stdClass) {
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
            : sprintf('%s/job/%s/%d', $this->baseUrl, \rawurlencode($job), $buildId);
    }

    /**
     * @param string $computerName
     *
     * @return Jenkins\Computer
     * @throws \RuntimeException
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

        if (!$infos instanceof \stdClass) {
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
        return sprintf('%s/job/%s', $this->baseUrl, \rawurlencode($job));
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
     * @throws \RuntimeException
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
     * @throws \InvalidArgumentException
     */
    public function createJob($jobname, $xmlConfiguration)
    {
        $url  = sprintf('%s/createItem?name=%s', $this->baseUrl, \rawurlencode($jobname));
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
            throw new \InvalidArgumentException(sprintf('Job %s already exists', $jobname));
        }
        if (curl_errno($curl)) {
            throw new \RuntimeException(sprintf('Error creating job %s', $jobname));
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
        $url  = sprintf('%s/job/%s/config.xml', $this->baseUrl, \rawurlencode($jobname));
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
        $url  = sprintf('%s/job/%s/config.xml', $this->baseUrl, \rawurlencode($jobname));
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error during getting configuration for job %s', $jobname));

        return $ret;
    }

    /**
     * @param Jenkins\Executor $executor
     *
     * @throws \RuntimeException
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
     * @throws \RuntimeException
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
     * @throws \RuntimeException
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
     * @throws \RuntimeException
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
        $url  = sprintf('%s/job/%s/%s/consoleText', $this->baseUrl, \rawurlencode($jobname), $buildNumber);
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);

        return curl_exec($curl);
    }

    /**
     * @param string $jobName
     * @param        $buildId
     *
     * @return array
     * @internal param string $buildNumber
     *
     */
    public function getTestReport($jobName, $buildId)
    {
        $url  = sprintf('%s/job/%s/%d/testReport/api/json', $this->baseUrl, \rawurlencode($jobName), $buildId);
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

        if (!$infos instanceof \stdClass) {
            throw new \RuntimeException($errorMessage);
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
    public function execute($uri, array $curlOptions)
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
    public function getComputers()
    {
        $return = $this->execute(
            '/computer/api/json', array(
                \CURLOPT_RETURNTRANSFER => 1,
            )
        );
        $infos  = json_decode($return);
        if (!$infos instanceof \stdClass) {
            throw new \RuntimeException('Error during json_decode');
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
    public function getComputerConfiguration($computerName)
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
            throw new \RuntimeException($errorMessage);
        }
        $info = curl_getinfo($curl);

        if ($info['http_code'] === 403) {
            throw new \RuntimeException(sprintf('Access Denied [HTTP status code 403] to %s"', $info['url']));
        }
    }
}
