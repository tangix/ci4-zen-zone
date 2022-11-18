# Getting localstack to run

We are hosting our projects with Amazon Web Services running in Elastic Container Services. Most of the AWS infrastructure is hidden from the code because the code doesn't directly communicate with the AWS services. One exception exists - we are using the [AWS Simple Queue Service](https://aws.amazon.com/sqs/) (SQS). For this we have added the AWS SDK `aws/aws-sdk-php` with composer.

SQS communicates over a REST API. The problem is now how to test this locally and the solution is [`localstack`](https://localstack.cloud/) which is a simulator for many of the AWS services, but running in your local environment. 

In the old development environment PHP ran directory on the host machine and `localstack` in a Docker container. Communication between PHP and the simulated SQS was not a problem because `localstack` could bind to ` localhost`.

In the new development environment, both `localstack` and PHP are running in different containers and need to be able communicate. Making things more complicated is that the URI of the simulated SQS queues must match.

SQS generates URIs with the hostname `eu-central-1.queue.localhost.localstack.cloud` so this hostname must be possible to resolve from the container running PHP.

Fortunately there is a way to get this working in Docker by using network `aliases`!

## Container networking

To create an alias to a container, the docker-compose file needs a `networks:` configuration:

```
networks:
  default:
```

Defining the `localstack` container is then straight forward:

```
    localstack:
        container_name: localstack_main
        image: localstack/localstack
        ports:
          - "127.0.0.1:4566:4566"            # LocalStack Gateway
          - "127.0.0.1:4510-4559:4510-4559"  # external services port range
          - "127.0.0.1:53:53"                # DNS config (only required for Pro)
          - "127.0.0.1:53:53/udp"            # DNS config (only required for Pro)
          - "127.0.0.1:8443:443"              # LocalStack HTTPS Gateway (only required for Pro)
        environment:
          LOCALSTACK_API_KEY: ${LOCALSTACK_API_KEY-} 
          EDGE_PORT: 4566
          SQS_ENDPOINT_STRATEGY: domain
        networks:
          default:
            aliases:
              - eu-central-1.queue.localhost.localstack.cloud
```

The key setting here is `aliases:` where the hostname is defined. For this to work properly in `localstack`, the env variable `SQS_ENDPOINT_STRATEGY` needs to be set to `domain`. When creating queues, the region needs to be included as:

```
msa@sindre _devenv % awslocal sqs create-queue --queue-name captivate-devel --region eu-central-1
{
    "QueueUrl": "http://eu-central-1.queue.localhost.localstack.cloud:4566/000000000000/captivate-devel"
}
```

## PHP container command

Unfortunately we are only half-way there. When running PHP in CLI mode the network needs to be set to the same, otherwise the alias will have no effect. The `docker run` command is updated to contain `--network container:localstack_main` indicating that the PHP execution container should belong to the same network as the `localstack_main` container. The `--hostname` setting is not compatible with this so any side-effects may still occur.