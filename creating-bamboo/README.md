# Creating a CI4 project in BAMBOO

Sounds like an easy task, doesn't it? Create the project, put the code in git, create a build project in BAMBOO and upload to the AWS ECR Registry. Well, today not so much! 

## Problem 1: creating a docker build container

In BAMBOO we run the build process inside a docker container to be able to build for multiple PHP versions. This docker image is pretty basic PHP 8.1 installation based on `php:8.1-apache` and with some additional tools, notably `composer`. Here the problem was to remove the dependencies on `mcrypt` library that (finally) is removed.

The build directory is mounted by BAMBOO into the build container and all commands are executed in the contrainer.

## Problem 2: getting composer to authenticate to private repositories

In our projects we have two private libraries, one publicly available on the Internet and one internal. To prepare for this project we have created our own composer package and are hosting it on our git server (Atlassian BitBucket). All works fine on the development machines because once authenticated, this is stored. How to solve this in the build container?

Tried multiple combinations of setting the `COMPOSER_AUTH` described in [composer docs](https://getcomposer.org/doc/articles/authentication-for-private-packages.md). Never managed to get the required JSON-formatted string to be set in the docker container. Sigh!

Since the build container is kept private I gave up and put a JSON-formatted file containing the credentials into `/root/.composer/`, solving this issue.

With authentication in place and some trail-and-errors later I found that `git` is not included in the stock `php:8.1-apache` so some modifications were required before all worked well with the BitBucket server.

## Problem 3: PHP required version in composer.json

With the many changes from PHP 7.4.x to 8.1.x the composer install in the build container wasn't working correctly. During our porting to PHP 8.1, we have had `"php": "^7.4||^8.1"` set in `composer.json`. Running in a native 8.1 build container, the checked in `composer.lock` contained old dependencies that couldn't be satisfied in PHP 8.1.

Solved this by removing `^7.4` from the required PHP version.

## Problem 4: Don't run composer update on the build server!

Copying an old BAMBOO build definition to get started the FARGATE docker images started to be build. However they didn't start properly in FARGATE. After much troubleshooting the solution was found. In the build process we do

```
composer install
./vendor/bin/phpunit
composer --no-dev update
```

The stray `update` is interesting in many ways. One obvious is that we are not delivering the code that has been tested with `phpunit`. Another is that with the just released update to CodeIgniter, everything broke! CodeIgniter 4.2.0 required an updated `public/index.php` file and since I wasn't aware of the update being performed it took some time to solve.

The problem was hidden in plain sight in BAMBOO's log-file but I just checked the first `composer install` command to make sure all was installed correctly and not upgraded...

```
error	07-Jun-2022 16:46:14	  - Upgrading codeigniter4/framework (v4.1.9 => v4.2.0)
```

## Problem 5: Make sure to read the AWS docs (and understand them!)

For this particular project, I decided to use AWS CloudMap instead of a full load-balancer. Mainly because this is an internal backend service and also to reduce costs (Application Load Balancers are quite expensive and can not be used internally in the VPC).

Multiple FARGATE instances of this project will be running in AWS ECS and they will be registered to one DNS record and removed when the container is no longer running. Other components of the system will use the CodeIgniter's CURL library to connect to the backend using the DNS name. Since CloudMap is built on top of AWS's DNS service Route53 the docs recommended health-checks running to make sure the instances are healthy.

After many attempts it was obvious that the FARGATE instances require a public IP-address assigned to be able to register in CloudMap! Also, health-checks are not supported on private CloudMap domains! Talk about Catch 22! So, the internal FARGATE instances all have public IP-addresses that are allocated but not used for anything else that registering with the CloudMap service record. Will re-visit this to make sure I got everything correct. (The public IP-addresses are blocked in the SecurityGroup firewall rule, only exposing the public Application Load Balancer).