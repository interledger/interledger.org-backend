<?php

/**
 * @file
 * Hooks provided by GraphQL Compose Routes module.
 */

/**
 * Alter the incoming URL path for resolution to Url.
 *
 * Potential uses could be prefixed paths or adhoc corrections.
 *
 * @param string $path
 *   The incoming path.
 */
function hook_graphql_compose_routes_incoming_alter($path): void {
  if (preg_match('/^\/color/i', $path)) {
    $path = '/colour';
  }
}
