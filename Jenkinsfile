/* groovylint-disable */


void setBuildStatus(String message, String state) {
    step([
        $class: 'GitHubCommitStatusSetter',
        reposSource: [$class: 'ManuallyEnteredRepositorySource', url: 'https://github.com/ooobii/jenkins-php-api'],
        contextSource: [$class: 'ManuallyEnteredCommitContextSource', context: 'jenkinsci/build'],
        errorHandlers: [[$class: 'ChangingBuildStatusErrorHandler', result: 'UNSTABLE']],
        statusResultSource: [ $class: 'ConditionalStatusResultSource', results: [[$class: 'AnyBuildResult', message: message, state: state]] ]
    ])
}


pipeline {
    agent { label 'linux' }
    stages {
        stage('Clone Repository') {
            steps {
                checkout([$class: 'GitSCM',
                    branches: [[name: "${GIT_BRANCH}"]],
                    extensions: [[$class: 'WipeWorkspace']],
                    userRemoteConfigs: [[url: 'git@github.com:ooobii/jenkins-php-api.git']]
                ])
            // sh "git clone --branch $BRANCH_NAME git@github.com:ooobii/jenkins-php-api.git ."
            }
        }
        stage('Install Composer') {
            steps {
                sh '''
                curl -sS https://getcomposer.org/installer -o composer-setup.php
                mkdir composer
                php composer-setup.php --install-dir=./composer --filename=composer
                rm -rf composer-setup.php
                '''
            }
        }
        stage('Install Composer Pkgs') {
            steps {
                sh './composer/composer install'
            }
        }
        stage('Remove Composer') {
            steps {
                sh 'rm -rf ./composer'
            }
        }
    }

    post {
        success {
            setBuildStatus('Build Successful', 'SUCCESS')
        }
        failure {
            setBuildStatus('Failure', 'FAILURE')
        }
        unstable {
            setBuildStatus('Unstable', 'UNSTABLE')
        }
        always {
            junit 'test/logs/*.xml'
        }
    }
}