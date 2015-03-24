# php-redis-read-replicas
Using Redis read replicas (e.g. AWS ElastiCache) to scale out read performance.

## Prerequisites
You need to have ** Redis PECL ** extension.

`sudo pecl install redis`

## Redis configuration

Run new Redis Cache cluster in AWS.

* Engine version 2.8.x
* Enable replication
* Enable Multi-AZ
* 1 or more Read Replicas

Elasticache is only accessible in the AWS environment (you cannot authorize your local IP to access it).

You can also run your own local Redis master-slave setup by running read replica in the different port, lets say `port 16379` and defining `slaveof 127.0.0.1 6379` in the config file.




