# pharcc

pharcc is a command-line tool that converts your php project into a single, executable `.phar` file for easy distribution.

[![Build Status](https://travis-ci.org/cbednarski/pharcc.png?branch=master)](https://travis-ci.org/cbednarski/pharcc) [![Roadmap Items](https://badge.waffle.io/cbednarski/pharcc.png?label=ready)](https://waffle.io/cbednarski/pharcc)

### Installation

pharcc requires php 5.3+. You must also set `phar.readonly = Off` in your `php.ini` or you will be unable to compile `.phar` files.

#### Via `.phar`

The easiest way to get it working is to download a tagged [`pharcc.phar`](https://github.com/cbednarski/pharcc/releases) release, and put this on your path. For example:

    $ wget https://github.com/cbednarski/pharcc/releases/download/v0.2.3/pharcc.phar
    $ chmod +x pharcc.phar
    $ sudo mv pharcc.phar /usr/local/bin/pharcc

#### Via composer global

If you have composer installed, you can use composer's global install option instead (and this makes it easier to keep pharcc up-to-date):

    $ composer global require cbednarski/pharcc=dev-master

Add `~/.composer/vendor/bin/` to your path and you're good to go. Maybe something like this, depending on your setup:

    $ echo 'export PATH=$PATH:$HOME/.composer/vendor/bin' >> ~/.bash_profile

Thanks [@clue](https://github.com/clue)

#### From Source

    $ git clone https://github.com/cbednarski/pharcc
    $ cd pharcc
    $ make install
    -- or --
    $ make install-dev

`install` will build a phar and put it in `/usr/local/bin`, while `install-dev` will point a symlink at the pharcc binstub so you can hack on the code.

### Usage

pharcc is a command-line tool that examines your project and produces a `.phar` file. `cd` into your project root and run the following:

    $ pharcc init
    $ pharcc build

You can also use pharcc as a library and call the internals directly, but if you do that I'm going to assume you're comfortable reading the code yourself. It's pretty short. Comments are welcome!

### How it works

A phar is essentially a slew of php files that are concatenated together to create a single, executable php file, complete with assets and such. pharcc reads a [`.pharcc.yml`](https://github.com/cbednarski/pharcc/blob/master/src/cbednarski/Pharcc/Resources/pharcc.yml) file from the root of your project and, combined with some assumed conventions, builds an executable phar for you.

I assume the following:

- You're using a [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) project structure (more below)
- Your app is a console application

Based on our assumptions, I've selected some sensible defaults for which files to include or exclude from your `.phar` file, which you can customize by editing the `.pharcc.yml` file. If your application is not a console app, you can omit `main` from the config.

### Your Project Layout

I assume your project layout looks something like this, which is pretty standard for PSR-0 applications:

    .
    ├── LICENSE
    ├── bin
    │   └── pharcc
    ├── composer.json
    ├── readme.md
    ├── src
    │   └── cbednarski
    │       └── Pharcc
    ├── tests
    │   └── cbednarski
    │       └── Pharcc
    └── vendor
        ├── autoload.php
        ├── cbednarski
        │   └── fileutils
        ├── composer
        │   ├── ClassLoader.php
        └── symfony
            ├── console
            ├── finder
            └── yaml

If your application doesn't look like this pharcc will probably still work, but you'll need to tweak your `.pharcc.yml` with some other appropriate includes / excludes.

### Version Reporting

If your project is in git, uses semver tags for releases, and uses the symfony console component, pharcc can report the version of your application on the commandline. For example:

    $ pharcc --version
    pharcc version 0.2.3

To make this work in your app, you'll need to add `cbednarski\Pharcc\Git::getVersion(__DIR__)` to your bin file.

```php
use cbednarski\Pharcc\Git;

$application = new Symfony\Component\Console\Application('pharcc', Git::getVersion(__DIR__));
```

Note: The current implementation is pretty magic, and only works if you worship the git, semver, and symfony console component gods.

### Contributing

Contributions are welcome! Please don't hesitate to file a bug, pull-request a feature, or let me know what could be made easier to use.

Check out the list of ready-for-dev items here: [![Roadmap Items](https://badge.waffle.io/cbednarski/pharcc.png?label=ready)](https://waffle.io/cbednarski/pharcc) Here are some guidelines to follow:

- The spirit of this project is to be simple and solve the general use case of creating phar files. It currently supports console applications and I'm willing to accept pull requests to add the ability to make a web app phar, like the one for phpMyAdmin, and a self-update command. Other large features, maybe. Get in touch.
- If you file a bug please include steps to repro, or even better, link me to a git hash that's failing to compile properly (make sure it includes your `.pharcc.yml` file).
- Code follows PSR-2 formatting rules. Use [php-cs-fixer](https://github.com/fabpot/PHP-CS-Fixer) to reformat your code before you PR.
- Use phpunit to run the unit tests and make sure they pass.
- Please add tests if you add a feature or fix a bug.
