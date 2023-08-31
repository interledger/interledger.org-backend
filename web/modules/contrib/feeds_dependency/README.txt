CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------
This module allows to add a Feed as a dependency to an another Feed. Importing
a Feed will import first the feed set as a dependency, and then main feed will
be imported.

This is useful if you want for example process in first a feed which import
image to a media entity, and then run the feed which import content with a
reference to the media entity created previously.

This module provide a checkbox too to clear the feed set as a dependency when
the main feed is cleared.

REQUIREMENTS
------------
This module requires the Feeds module.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.


CONFIGURATION
-------------
- Configure the feeds type you need. For example a Feed to import media entity
  and an another feed to import node.
- Create two Feeds based on the previously feed types created.
- You can select a feed to be set as a dependency using the entity reference
  field in the edit form. You can then check the option to clear the feed set
  as a dependency if the current feed is cleared (Delete items).
- Clicking on the Import operation will first process the feed set as a
  dependency and then process the current feed.

TROUBLESHOOTING
---------------


FAQ
---


MAINTAINERS
-----------
Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile

SPONSORS
--------
Module sponsored by Gallimedia (https://www.gallimedia.com)
