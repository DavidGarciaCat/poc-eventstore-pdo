# POC for PDO vs EventStore

This is a VERY basic approach of how much effort is required for
base implementations of PDO and EventStore to persist data.

Please note that code reusability was not a priority.

## Run the POC

Step-by-step guide

```shell script
# Clone the repository
git clone git@github.com:DavidGarciaCat/poc-eventstore-pdo.git

# Change to POC's folder
cd poc-eventstore-pdo

# Boot up Docker containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Run PHPUnit
docker-compose exec php vendor/bin/phpunit tests

# Stop Docker containers
docker-compose down
```
