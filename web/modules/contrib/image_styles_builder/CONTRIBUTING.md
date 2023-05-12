# Developing on Image Styles Builder

* Issues should be filed at
https://www.drupal.org/project/issues/image_styles_builder
* Pull requests can be made against
https://github.com/antistatique/drupal-image-styles-builder/pulls

## 📦 Repositories

Drupal repo

  ```bash
  git remote add drupal \
  git@git.drupal.org:project/image_styles_builder.git
  ```

Github repo

  ```bash
  git remote add github \
  git@github.com:antistatique/drupal-image-styles-builder.git
  ```

## 🔧 Prerequisites

First of all, you need to have the following tools installed globally
on your environment:

  * drush
  * The latest dev release of Image Styles Builder
  * docker
  * docker-compose

### Project bootstrap

Once run, you will be able to access to your fresh installed Drupal on `localhost::8888`.

    docker-compose build --pull --build-arg BASE_IMAGE_TAG=9.5 drupal
    # (get a coffee, this will take some time...)
    docker-compose up --build -d drupal
    docker-compose exec -u www-data drupal drush site-install standard --db-url="mysql://drupal:drupal@db/drupal" -y

    # You may be interesed by reseting the admin password of your Docker.
    docker-compose exec dev drush user:password admin admin

    # Enable the module to use it.
    docker-compose exec dev drush en image_styles_builder

## 🏆 Tests

We use the [Docker for Drupal Contrib images](https://hub.docker.com/r/wengerk/drupal-for-contrib) to run testing on our project.

Run testing by stopping at first failure using the following command:

    docker-compose exec -u www-data drupal phpunit --group=image_styles_builder --no-coverage --stop-on-failure --configuration=/var/www/html/phpunit.xml

## 🚔 Check Drupal coding standards & Drupal best practices

During Docker build, the following Static Analyzers will be installed on the Docker `drupal` via Composer:

- `drupal/coder^8.3.1`  (including `squizlabs/php_codesniffer` & `phpstan/phpstan`),

The following Analyzer will be downloaded & installed as PHAR:

- `phpmd/phpmd`
- `sebastian/phpcpd`
- `wapmorgan/PhpDeprecationDetector`
- `mglaman/drupal-check`
- `vimeo/psalm`

### Command Line Usage

    ./scripts/hooks/post-commit
    # or run command on the container itself
    docker-compose exec dev bash

#### Running Code Sniffer Drupal & DrupalPractice

https://github.com/squizlabs/PHP_CodeSniffer

PHP_CodeSniffer is a set of two PHP scripts; the main `phpcs` script that tokenizes PHP, JavaScript and CSS files to
detect violations of a defined coding standard, and a second `phpcbf` script to automatically correct coding standard
violations.
PHP_CodeSniffer is an essential development tool that ensures your code remains clean and consistent.

  ```
  $ docker-compose exec dev phpcs
  ```

Automatically fix coding standards

  ```
  $ docker-compose exec dev phpcbf
  ```

#### Running PHP Mess Detector

https://github.com/phpmd/phpmd

Detect overcomplicated expressions & Unused parameters, methods, properties.

  ```
  $ docker-compose exec dev phpmd ./web/modules/custom text ./phpmd.xml \
  --suffixes php,module,inc,install,test,profile,theme,css,info,txt --exclude *Test.php,*vendor/*
  ```

  ```
  $ docker-compose exec dev phpmd text ./phpmd.xml \
  --suffixes php,module,inc,install,test,profile,theme,css,info,txt --exclude *Test.php,*vendor/*
  ```

  ```
  $ docker-compose exec dev phpmd ./behat text ./phpmd.xml -suffixes php
  ```

#### Running PHP Copy/Paste Detector

https://github.com/sebastianbergmann/phpcpd

`phpcpd` is a Copy/Paste Detector (CPD) for PHP code.

  ```
  $ docker-compose exec dev phpcpd ./web/modules/custom ./behat \
--names=*.php,*.module,*.inc,*.install,*.test,*.profile,*.theme,*.css,*.info,*.txt --names-exclude=*.md,*.info.yml \
--exclude tests --exclude vendor/ --ansi
  ```

#### Running PhpDeprecationDetector

https://github.com/wapmorgan/PhpDeprecationDetector

A scanner that checks compatibility of your code with PHP interpreter versions.

  ```
  $ docker-compose exec dev phpdd ./web/modules/custom ./behat \
    --file-extensions php,module,inc,install,test,profile,theme,info --exclude vendor
  ```

#### Running Drupal-Check

https://github.com/mglaman/drupal-check

Built on PHPStan, this static analysis tool will check for correctness (e.g. using a class that doesn't exist),
deprecation errors, and more.

While there are many static analysis tools out there, none of them run with the Drupal context in mind.
This allows checking contrib modules for deprecation errors thrown by core.

  ```
  $ ./vendor/bin/drupal-check -dvvv ./web/modules/contrib/image_styles_builder/ --format=checkstyle \
  --exclude-dir=*vendor* --no-progress
  ```

### Enforce code standards with git hooks

Maintaining code quality by adding the custom post-commit hook to yours.

  ```bash
  cat ./scripts/hooks/post-commit >> ./.git/hooks/post-commit
  ```
