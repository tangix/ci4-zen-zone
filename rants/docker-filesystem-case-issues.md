# Differences in case-handling between dev and production

Just found an interesting issue that manifested itself in production but wasn't caught in development and testing.

We have a config file in `app/Config/AWS.php` that is working on the local development machine (running PHP in a docker container, mounting the macOS directory). The code refers to this configuration file as `$config = config('Aws');` and all works locally when developing and testing. 

After preparing the Docker for the production system - this particular piece of code fails, returning `null` and generating a 500 error.

Huh?

Turns out the mounting of the macOS file-system in Docker is in fact case-insensitive even though the Docker image running php and apache is a Linux system which is case-sensitive. In the local development Docker, the following commands all work:

```
more app/Config/aws.php
more app/Config/AWS.php
more app/Config/Aws.php
```

Running in the production Docker the issue is that the argument to `config()` is `Aws` so the framework tries to locate the file named `app/Config/Aws.php` but fails!

Lesson learned.