# .env file and Docker Problems

Just ran into some issues with CI4, .env and Docker. 

In a project I have a CI4 application running in a AWS FARGATE Container. There I have set several configuration items in the ``.env`` file:

```
challenge.secret = ****
challenge.id = mm2021.dev

# The time spent in the waiting room
challenge.waitingroom = 10

# The length of the registration period
challenge.regperiod = 120

# The length in minutes
challenge.length = 25

# -1 means start at next 10 minute interval
challenge.start = -1
```

The application was packaged as a Docker container based on ``php:7.3.26-apache-stretch``. Some of the configuration was also overridden using FARGATE container environment variables.

For some reason I couldn't get the configuration to work in the application - what gives?!?

## Docker and Bash secrets

Turns out that the Docker image I am using is based on Ubuntu and using ``bash`` as shell. In ``bash`` a variable-name is defined as:

_A word  consisting  only  of alphanumeric characters and underscores, and beginning with an alphabetic character or an  underscore.  Also referred to as an identifier._

Huh, say what?? I can't have ``.`` in ``.env`` and set that in the Docker environment - period. Back to drawing-board.
