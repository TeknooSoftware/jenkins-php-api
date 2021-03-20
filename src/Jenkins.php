<?php

namespace ooobii;

class Jenkins {

    /**
     * The URL configured for use in each API request.
     * 
     * @var string
     */
    private $baseUrl;

    /**
     * The object used to store the primary API endpoint result, storing information about the Jenkins instance.
     *
     * @var stdClass|null;
     */
    private $jenkins = NULL;

    /**
     * Signifies whether or not to retrieve and send anti-CSRF crumb tokens with each request to the Jenkins API.
     *
     * Defaults to `FALSE` for backwards compatibility.
     *
     * @var boolean
     */
    private $crumbsEnabled = FALSE;

    /**
     * The anti-CSRF crumb to use for each request to the Jenkins API.
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     *
     * @var string
     */
    private $crumb;

    /**
     * The header to use for sending anti-CSRF crumbs to the Jenkins API.
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     *
     * @var string
     */
    private $crumbRequestField;


    /**
     * Create a new instance of the Jenkins management class.
     * 
     * @param string $baseUrl The URL of the Jenkins instance to connect to.
     */
    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Enables the use of anti-CSRF crumbs on requests to the Jenkins API.
     *
     * @return bool
     *   * `TRUE` if crumbs were enabled successfully.
     *   * `FALSE` if an error occurred when attempting to enable crumbs.
     */
    public function enableCrumbs() {

        //make a request to Jenkins for a crumb.
        $crumbResult = $this->requestCrumb();

        //make sure that the result returned successfully.
        if (!$crumbResult || !is_object($crumbResult)) {

            //the crumb failed to be obtained, disable crumbs and return failure.
            $this->crumbsEnabled = false;
            return FALSE;

        }

        //the crumb was obtained; store the results and mark crumbs as enabled.
        $this->crumbsEnabled = true;
        $this->crumb             = $crumbResult->crumb;
        $this->crumbRequestField = $crumbResult->crumbRequestField;

        //report success
        return TRUE;

    }

    /**
     * Disable the use of anti-CSRF crumbs on requests to the Jenkins API.
     *
     * @return bool 
     *   * `TRUE` if crumbs were disabled successfully.
     *   * `FALSE` if crumbs were already disabled.
     */
    public function disableCrumbs() {
        
        //check to see if crumbs are already disabled.
        if(!$this->crumbsEnabled) return FALSE;

        //if not, destroy the crumb data obtained, and set the flag to 'FALSE'.
        $this->crumbsEnabled = false;
        $this->crumb             = NULL;
        $this->crumbRequestField = NULL;

        //return success.
        return TRUE;

    }

    /**
     * Signifies whether or not to retrieve and send anti-CSRF crumb tokens with each request to the Jenkins API.
     *
     * @return boolean Whether or not crumbs have been enabled
     */
    public function areCrumbsEnabled() {
        return $this->crumbsEnabled;
    }

    /**
     * Requests a new crumb from the Jenkins API to authenticate the the current session.
     *
     * @return stdClass The crumb object that's delivered back from the Jenkins API.
     */
    public function requestCrumb() {

        //define the request URL for curl to execute.
        $url = "{$this->baseUrl}/crumbIssuer/api/json'";

        //initialize curl with the URL compiled to obtain the crumb.
        $curl = curl_init($url);

        //ensure that the body is not returned to the console STDOUT.
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, TRUE);

        //execute the GET request through curl.
        $result = curl_exec($curl);

        //ensure the crumb obtained was valid.
        $this->validateCurl($curl, 'Error getting CSRF crumb.');

        //convert the crumb result from JSON to a PHP object.
        $crumbResult = json_decode($result);

        //make sure the json_decode operation succeeded.
        if (json_last_error() !== \JSON_ERROR_NONE) throw new \RuntimeException('An error occurred when converting the Jenkins crumb response into a PHP object.');

        //return the serialized crumb object from Jenkins.
        return $crumbResult;

    }

    /**
     * Returns the raw HTTP header content to include in request to the Jenkins API for crumb authentication.
     *
     * @return string The HTTP header to add to curl requests when crumbs are enabled.
     */
    public function getCrumbHeader() {
        return "$this->crumbRequestField: $this->crumb";
    }

    /**
     * Determines if the Jenkins instance specified is available to fulfill API or SSH requests.
     * 
     * @return boolean
     *   * `TRUE` if the Jenkins instance's API set at construction is available.
     *   * `FALSE` if the Jenkins instance's API set at construction is *not* available.
     */
    public function isAvailable() {

        //load the curl request from the base url.
        $curl = curl_init($this->baseUrl . '/api/json');

        //disable the content of the curl request from being echoed to STDOUT.
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);

        //execute the compiled curl request.
        curl_exec($curl);

        if (curl_errno($curl)) {
            
            //an error occurred when making the request to the Jenkins API.
            //return failure.
            return FALSE;

        } else {

            //the curl request ran successfully, but that just means Jenkins is up.
            //also make sure that a job queue is available to read from; if not, the Jenkins instance cannot execute jobs.
            try {  
                $this->getQueue();
            } catch (\RuntimeException $e) {

                //job queue failed to be obtained. return failure.
                return FALSE;

            }
        }

        //checks pass, return availability.
        return TRUE;
    }

    /**
     * Load root instance data received from the Jenkins API before attempting to access data from it.
     * 
     * @return void
     * @throws \RuntimeException If the request to the Jenkins API fails, a `\RuntimeException` is thrown.
     */
    private function initialize() {

        //if the root Jenkins data for this URL has already been obtained, don't continue.
        if($this->jenkins !== null) return; 

        //load the curl request.
        $curl = curl_init($this->baseUrl . '/api/json');

        //set the options for the curl request.
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);

        //execute the curl request to the compiled Jenkins API url.
        $result = curl_exec($curl);

        //ensure that the curl's execution was valid.
        $this->validateCurl($curl, sprintf('Error during getting list of jobs on %s', $this->baseUrl));

        //set the Jenkins object to the decoded JSON result from the curl request.
        $this->jenkins = json_decode($result);

        //make sure that an error didn't occur when decoding the JSON data.
        if (json_last_error() !== \JSON_ERROR_NONE || !$this->jenkins) 
            throw new \RuntimeException('An error occurred when attempting to initialize the local Jenkins object; unable to decode API result data into JSON.');

    }

    /**
     * Returns an array of job names that are present in the currently focused folder.
     * 
     * @throws \RuntimeException
     * @return array<string>
     */
    public function getAllJobNames() {
        $this->initialize();

        $jobs = array();
        foreach ($this->jenkins->jobs as $job) {
            $jobs[] = $job->name;
        }

        return $jobs;
    }

    /**
     * Returns an array of job objects that are present in the currently focused folder.
     * 
     * @return array<Jenkins\Job>
     */
    public function getAllJobs() {
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
            $url = sprintf('%s/job/%s/build', $this->baseUrl, $jobName);
        } else {
            $url = sprintf('%s/job/%s/buildWithParameters', $this->baseUrl, $jobName);
        }

        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_POST, 1);
        curl_setopt($curl, \CURLOPT_POSTFIELDS, http_build_query($parameters));

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
        $url  = sprintf('%s/job/%s/api/json', $this->baseUrl, $jobName);
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
        $url  = sprintf('%s/job/%s/doDelete', $this->baseUrl, $jobName);
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
        $url  = sprintf('%s/job/%s/%d/api/json%s', $this->baseUrl, $job, $buildId, $tree);
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
            : sprintf('%s/job/%s/%d', $this->baseUrl, $job, $buildId);
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
        return sprintf('%s/job/%s', $this->baseUrl, $job);
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
        $url  = sprintf('%s/createItem?name=%s', $this->baseUrl, $jobname);
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
        $url  = sprintf('%s/job/%s/config.xml', $this->baseUrl, $jobname);
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
        $url  = sprintf('%s/job/%s/config.xml', $this->baseUrl, $jobname);
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
        $url  = sprintf('%s/job/%s/%s/consoleText', $this->baseUrl, $jobname, $buildNumber);
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
        $url  = sprintf('%s/job/%s/%d/testReport/api/json', $this->baseUrl, $jobName, $buildId);
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
