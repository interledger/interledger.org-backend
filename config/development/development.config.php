<?php
$base_url = $_SERVER['BASE_URL'];
// nextjs
$config['next.next_site.client']['base_url'] = $base_url;
$config['next.next_site.client']['preview_url'] = $base_url . '/api/preview';
$config['next.next_site.client']['revalidate_url'] = $base_url . '/api/revalidate';

// graphql
$config['graphql.graphql_servers.test']['caching'] = true;
$config['graphql.graphql_servers.test']['debug_flag'] = 0;

// imagemagick
$config['imagemagick.settings']['path_to_binaries'] = '/usr/bin/';

// cdn
$config['cdn.settings']['scheme'] = 'https://';
$config['cdn.settings']['mapping']['domain'] = $_SERVER['IMAGE_CDN_DOMAIN'];
