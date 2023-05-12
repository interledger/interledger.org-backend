<?php

namespace Drupal\graphql_compose_domain\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Domain",
 * )
 */
class Domain extends SchemaTypeBase
{

  /**
   * {@inheritDoc}
   */
  public function toType(): Type
  {
    return new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('An Domain within the CMS.'),
      'fields' => fn () => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('The uuid of the Domain.'),
        ],
        'name' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The display name of the Domain.'),
        ],
      ],
    ]);
  }
}
