#!/usr/bin/env bash
cd /var/app/current && drush updatedb --no-cache-clear && mkdir -p keys && drush simple-oauth:generate-keys ../keys
