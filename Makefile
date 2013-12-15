all: init test
init:
	composer install
test:
	phpunit
phar:
	./bin/pharcc build
clean:
	rm -rf vendor/
	rm pharcc.phar