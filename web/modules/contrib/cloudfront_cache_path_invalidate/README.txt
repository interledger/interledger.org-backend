
Readme
================================================================================

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration

INTRODUCTION
================================================================================
Cloudfront Cache Path Invalidate module, you can manage the cache clear of
Amazon Cloudfront through a setting form.


REQUIREMENTS
================================================================================
Drupal 8.x
CloudFront Setup
    AWS Distribution ID
    AWS Access Key
    AWS Secret Key
    AWS Region

INSTALLATION
================================================================================
admin/config/services/cloudfront-invalidate-url


CONFIGURATION
================================================================================
This needs the following settings:
Please use below $settings with your AWS Credential in settings.php file

$settings['aws.distributionid'] = '';
$settings['aws.region'] = '';
$settings['s3fs.access_key'] = '';
$settings['s3fs.secret_key'] = '';
