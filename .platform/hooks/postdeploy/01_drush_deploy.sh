#!/usr/bin/env bash
cd /var/app/current && drush updatedb --no-cache-clear && drush cache:rebuild
