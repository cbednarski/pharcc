all: init test phar

init:
	composer install

test: init
	phpunit

phar: init
	./bin/pharcc build
	chmod +x pharcc.phar

clean:
	rm -rf vendor/
	rm pharcc.phar

install: phar
	mv $(PWD)/pharcc.phar /usr/local/bin/pharcc

install-dev: init
	ln -sf $(PWD)/bin/pharcc /usr/local/bin/pharcc