<?php

namespace Drupal\graphql_compose_extra_config_pages\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ConfigPages",
 * )
 */
class ConfigPages extends GraphQLComposeSchemaTypeBase
{

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('An Domain within the CMS.'),
      'fields' => fn () => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('The uuid of the Domain.'),
        ],
        'label' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The display name of the Domain.'),
        ],
      ],
    ]);

    return $types;
  }
}
