CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Entity Reference Purger removes orphaned (dangling) entity references when an 
entity is deleted.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/entity_reference_purger

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/entity_reference_purger


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Entity Reference Purger module as you would normally install a 
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for 
   further information.


CONFIGURATION
-------------

For config fields (fields added via Manage fields UI):  
 * Go to an entity reference field settings (e.g. /admin/structure/types/manage/article/fields/node.article.field_tags)
   and check the 'Remove orphaned entity references' checkbox.
 * If you have a lot of dangling references you might want to also enable the
   'Use queue' option for performance reasons.

For base fields:
 * Add the following code to your base field definition:

```
->setSetting('entity_reference_purger', [
  'remove_orphaned' => TRUE,
  'use_queue' => FALSE,
]);
```

MAINTAINERS
-----------

Current maintainers:
 * Bojan Milanković (bojanm) - https://www.drupal.org/u/bojanm
 * Goran Nikolovski (gnikolovski) - https://www.drupal.org/u/gnikolovski

This project has been sponsored by:
 * Studio Present - https://www.drupal.org/studio-present
