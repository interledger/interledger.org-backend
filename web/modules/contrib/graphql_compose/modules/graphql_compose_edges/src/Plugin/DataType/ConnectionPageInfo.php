<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * PageInfo Type.
 *
 * Provides information about a GraphQL paged result set ("Connection").
 *
 * @DataType(
 *   id = "page_info",
 *   label = @Translation("Page Info"),
 *   definition_class = "\Drupal\graphql_compose_edges\TypedData\Definition\PageInfoDefinition"
 * )
 */
class ConnectionPageInfo extends Map {}
