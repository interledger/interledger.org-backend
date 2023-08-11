# README #

### What is this repository for? ###

* Headless Drupal CMS

### How do I get set up? ###

* Install Drush https://github.com/drush-ops/drush-launcher#installation---phar
* `composer install`
* Set up database and configure in
`web/sites/default/settings.local.php`
* Reset the admin password
 `drush uli --uri http://admin.interledger.test`
* Once logged in create a folder in the root directory called `keys`. Then goto `/admin/config/people/simple_oauth` and select Generate Keys and input `../keys` and Generate

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
HASH_SALT
IMAGE_CDN

### Import/Export config and content? ###

After making any changes to the Drupal config the changes should be exported so that they can be applied to deployed sites.

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
