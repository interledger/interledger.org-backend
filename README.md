# README #

### What is this repository for? ###

* Headless Drupal CMS
* Version 1.0

### How do I get set up? ###

* composer install

### Deployment ###

#### Environment Variables ####
BASE_URL
ENVIRONMENT
RDS_DB_NAME
RDS_USERNAME
RDS_PASSWORD
RDS_HOSTNAME
RDS_PORT
MOUNT_DIR
FILE_SYSTEM_ID
CLOUDFRONT_DISTRIBUTIONID
CLOUDFRONT_REGION
AWS_ACCESS_KEY
AWS_SECRET_KEY

### Import/Export config and content? ###

To export your config and content run the following drush commands

```
drush cex
```

To import the lastest config run the following drush commands

```
cd /var/app/current
drush cim
drush cr
```

### Update drupal core ###
```
composer update drupal/core "drupal/core-*" --with-all-dependencies
drush updb
drush cr
```

### Update drupal modules ###
```
composer update drupal/modulename --with-dependencies
drush updb
drush cr
```
