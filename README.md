# Jenkins Management for PHP

This composer library is designed to facilitate interactions with Jenkins CI using its API or CLI via SSH.

This library is distributed with the [MIT License](https://tldrlegal.com/license/mit-license) applied to all work within.

## About this Fork
**Note:** This is a fork of the original repository created by [Jenkins Khan](https://github.com/jenkins-khan), located [here](https://github.com/jenkins-khan/jenkins-php-api). A huge thanks to them for starting this project. 

The primary focus of this fork is to facilitate additional functionality through the Jenkins CLI via. SSH. Since many publicly-accessible pieces of functionality are changing from the original, this fork is also published as it's own [Composer](http://getcomposer.org) package under my identity (ooobii).

## Installation

#### Package Installation

To begin utilizing Jenkins Management for PHP, you can install it using [Composer](http://getcomposer.org):

```bash
# Once composer is installed, you can require it within your project.
# Using this 'require' variant will install the latest stable release build.
composer require ooobii/jenkins-api

# To use the latest development build, .
composer require ooobii/jenkins-api:dev-master
```

#### Composer Installation

If you do not have [Composer](http://getcomposer.org) installed, you can download the binary to your project's folder:
```bash
# Always exercise caution when executing installer scripts from remote sources!
curl -sS https://getcomposer.org/installer | php

# This isn't required, but it's easier to refer to it this way.
mv composer.phar composer
```

Alternatively, you can install it on your system globally, so the command `composer` will be accessible anywhere:
``` bash
# Always exercise caution when executing installer scripts from remote sources!
wget "https://getcomposer.org/installer" -O composer-setup.php

# These 2 arguments will copy the binary to a folder in your $PATH, and remove the default extension.
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
```


## Basic Usage

### Connecting to a Jenkins Instance
Before anything, you need to instantiate the client:

```php
$jenkins = new \ooobii\Jenkins('http://host.org:8080');
```
**_OR:_**
```php
use \ooobii\Jenkins;
...
$jenkins = new Jenkins('http://host.org:8080');
```

If your Jenkins needs authentication, you need to pass a URL like this : `'http://user:token@host.org:8080'`.


Here are some examples of how to use it:


Get the color of the job
------------------------

```php
    $job = $jenkins->getJob("dev2-pull");
    var_dump($job->getColor());
    //string(4) "blue"
```


Launch a Job
------------

```php
    $job = $jenkins->launchJob("clone-deploy");
    var_dump($job);
    // bool(true) if successful or throws a RuntimeException
```


List the jobs of a given view
-----------------------------

```php
    $view = $jenkins->getView('madb_deploy');
    foreach ($view->getJobs() as $job) {
      var_dump($job->getName());
    }
    //string(13) "altlinux-pull"
    //string(8) "dev-pull"
    //string(9) "dev2-pull"
    //string(11) "fedora-pull"
```

List builds and their status
----------------------------

```php
    $job = $jenkins->getJob('dev2-pull');
    foreach ($job->getBuilds() as $build) {
      var_dump($build->getNumber());
      var_dump($build->getResult());
    }
    //int(122)
    //string(7) "SUCCESS"
    //int(121)
    //string(7) "FAILURE"
```


Check if Jenkins is available
-----------------------------

```php
    var_dump($jenkins->isAvailable());
    //bool(true);
```

For more information, see the [Jenkins API](https://wiki.jenkins-ci.org/display/JENKINS/Remote+access+API).


Coding standards
----------------

This projects follows PSR-0, PSR-1, PSR-2, PSR-4
