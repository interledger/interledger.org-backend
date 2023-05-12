<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for GraphQL Compose GraphQL type plugins.
 *
 * @Annotation
 */
class GraphQLComposeSchemaType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
