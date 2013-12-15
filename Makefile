all: init test phar
init:
	composer install
test:
	phpunit
phar:
	./bin/pharcc build
clean:
	rm -rf vendor/
	rm pharcc.phar