<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ViewPageInfo",
 * )
 */
class ViewPageInfo extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Information about the page in a view.'),
      'fields' => fn() => [
        'offset' => [
          'type' => Type::nonNull(Type::int()),
          'description' => (string) $this->t('Any result offset being used.'),
        ],
        'page' => [
          'type' => Type::nonNull(Type::int()),
          'description' => (string) $this->t('The current page being returned.'),
        ],
        'pageSize' => [
          'type' => Type::nonNull(Type::int()),
          'description' => (string) $this->t('How many results per page.'),
        ],
        'total' => [
          'type' => Type::nonNull(Type::int()),
          'description' => (string) $this->t('How many results total.'),
        ],
      ],
    ]);

    return $types;
  }

}
