<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_users\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\EnumType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "UserStatus",
 * )
 */
class UserStatus extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new EnumType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Whether the user is active or blocked.'),
      'values' => [
        'ACTIVE' => [
          'value' => 1,
          'description' => (string) $this->t('An active user is able to login on the platform and view content'),
        ],
        'BLOCKED' => [
          'value' => 0,
          'description' => (string) $this->t("A blocked user is unable to access the platform, although their content will still be visible until it's deleted."),
        ],
      ],
    ]);

    return $types;
  }

}
