# Headless Drupal CMS

## Prerequisites

- php 8.2: `brew install php`
- Composer 2: follow instructions at [https://getcomposer.org/download/](https://getcomposer.org/download/)
- ImageMagick: `brew install imagemagick`

## Local development

- Install Drush https://github.com/drush-ops/drush-launcher#installation---phar
- `composer install` from your Drupal root folder
- Copy `web/sites/example.settings.local.php` to `web/sites/default/settings.local.php` and configure database
- Make sure the `$base_url` in `settings.local.php` matches the node server running the frontend
- Make sure the `$settings['file_public_base_url']` in `settings.local.php` matches the cms url (this will be the cdn in production)
- Reset the admin password by running `drush uli --uri http://LOCAL_HOSTNAME`
- Generate oauth keys for previews `drush simple-oauth:generate-keys ../keys`
- Make sure the files from the backup are copied into the `/web/sites/default/files` folder

## Deployment

### Environment Variables

- BASE_URL
- ENVIRONMENT
- RDS_DB_NAME
- RDS_USERNAME
- RDS_PASSWORD
- RDS_HOSTNAME
- RDS_PORT
- MOUNT_DIR
- FILE_SYSTEM_ID
- CLOUDFRONT_DISTRIBUTIONID
- CLOUDFRONT_REGION
- AWS_ACCESS_KEY
- AWS_SECRET_KEY
- HASH_SALT
- IMAGE_CDN

### Import/Export config and content?

After making any changes to the Drupal config the changes should be exported so that they can be applied to deployed sites.

To export your config and content run the following `drush` commands

```
drush cex
```

To import the latest config run the following `drush` commands

```
cd /var/app/current
drush cim
drush cr
```

### Update Drupal core

```
composer update drupal/core "drupal/core-*" --with-all-dependencies
drush updb
drush cr
```

### Update Drupal modules

```
composer update drupal/modulename --with-dependencies
drush updb
drush cr
```

### Patching Drupal modules

Refer to https://www.drupal.org/docs/develop/using-composer/manage-dependencies#patches
