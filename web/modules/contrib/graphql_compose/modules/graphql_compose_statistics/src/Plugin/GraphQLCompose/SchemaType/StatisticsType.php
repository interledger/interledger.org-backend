<?php

namespace Drupal\graphql_compose_statistics\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Statistics",
 * )
 */
class StatisticsType extends GraphQLComposeSchemaTypeBase
{

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A paginated set of results.'),
      'fields' => fn () => [
        'success' => [
          'type' => Type::boolean(),
          'description' => (string) $this->t('Response of record view'),
        ],
      ],
    ]);

    $types[] = new ObjectType([
      'name' => 'StatisticsConnection',
      'description' => (string) $this->t('Response of record view'),
      'interfaces' => fn () => [
        static::type('Statistics'),
      ],
      'fields' => fn () => [
        'success' => Type::boolean(),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritDoc}
   *
   * Create bundle queries.
   */
  public function getExtensions(): array
  {
    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Mutation',
      'fields' => fn () => [
        'recordView' => [
          'type' => Type::nonNull(static::type('StatisticsConnection')),
          'description' => (string) $this->t('List of possible matching words'),
          'args' => [
            'id' => [
              'type' => Type::string(),
              'description' => (string) $this->t('ID'),
            ],
          ],
        ],
      ],
    ]);

    return $extensions;
  }
}
