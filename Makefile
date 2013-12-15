all: init test
init:
	composer install
test:
	phpunit
clean:
	rm -rf vendor/