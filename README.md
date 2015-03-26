# php-redis-read-replicas
The purpose of this class is to utilize Redis read replicas in AWS ElastiCache (or similar) to scale out read performance.
Keep in mind though that since Redis replication is asynchronous, you need to architect your application in such a way that you are comfortable with eventually complete data. For example, reading user profile or sessions from secondaries may not be such a great idea; however for example loading user comments from secondary is fine.

You'll set the read preference and use `getReadAdapter()` method, which (depending on your read preference) returns one of the available secondary servers or a primary.

## Prerequisites
You need to have ** Redis PECL ** extension.

`sudo pecl install redis`

## Redis configuration

Run new Redis Cache cluster in AWS:

* Engine version 2.8.x
* Enable replication
* Enable Multi-AZ
* 1 or more Read Replicas

Note that ElastiCache is only accessible in the AWS environment, and you cannot authorize your local IP to access it.

Run your own local Redis master-slave setup by running read replica in the different port, lets say `port 16379` and define `slaveof 127.0.0.1 6379` in the config file.




