.PHONY: composer tests

composer:
	COMPOSER_HOME="/tmp/composer-home" composer install --no-progress

tests:
	phpunit --bootstrap tests/bootstrap.php tests/