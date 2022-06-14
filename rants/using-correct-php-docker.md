# Using correct Docker image for PHP

For many years we have been using Docker image `php:<version>-apache-<distribution>`, for example `php:7.4.30-apache-buster`.

This has worked well, but with a recent [vulnerability discovered](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2022-22720), we noticed that the image based on the `buster` distribution wasn't updated with a patched version of apache! Checking the `bullseye` image and apache was updated within days after a fix the vulnerability was released. Also the `php:<version>-apache` image was updated with the latest version.

As a safe-guard we have always been using images with a named distribution to make sure the commands in the `Dockerfile` will work when installing dependencies for the php build process. With the slowness to patch for vulnerabilities we have re-thought this and came to the conclusion it's better to let the build-process break that running versions with known vulnerabilities.

**We have thus changed to use images without the distribution specified.**

Furthermore, specifying the specific PHP release is probably no longer necessary and just creates a lot of work when PHP updates to a new patch-version (8.1.6 -> 8.1.7 for example). Instead of specifying the image `8.1.7-apache` and then updating all projects, specifying `8.1-apache` and just re-running the build-process in BAMBOO would save some time. The drawback is that there will be no record in JIRA or `git` of the update, something to think about.