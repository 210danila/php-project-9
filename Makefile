PORT ?= 8000
install:
	composer install

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public
	composer exec --verbose phpcs -- --standard=PSR12 app

.PHONY: tests
tests:
	composer exec --verbose phpunit tests

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

