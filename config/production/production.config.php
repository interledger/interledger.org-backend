<?php
$base_url = $_SERVER['BASE_URL'];
// nextjs
$config['next.next_site.tuos_dev']['base_url'] = $base_url;
$config['next.next_site.tuos_dev']['preview_url'] = $base_url . '/api/preview';
$config['next.next_site.tuos_dev']['revalidate_url'] = $base_url . '/api/revalidate';

// graphql
$config['graphql.graphql_servers.test']['caching'] = true;
$config['graphql.graphql_servers.test']['debug_flag'] = 0;

// imagemagick
$config['imagemagick.settings']['path_to_binaries'] = '/usr/bin/';
