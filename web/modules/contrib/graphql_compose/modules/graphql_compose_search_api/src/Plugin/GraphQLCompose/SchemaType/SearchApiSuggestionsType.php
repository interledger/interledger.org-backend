<?php

namespace Drupal\graphql_compose_search_api\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "SearchApiSuggestionsConnection",
 * )
 */
class SearchApiSuggestionsType extends GraphQLComposeSchemaTypeBase
{

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] =  new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A paginated set of results.'),
      'fields' => fn () => [
        'suggestions' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
          'description' => (string) $this->t('Suggestions for search'),
        ],
      ],
    ]);

    $types[] = new ObjectType([
      'name' => 'SuggestionsConnection',
      'description' => (string) $this->t('A paginated set of results for Nodes'),
      'interfaces' => fn () => [
        static::type('SearchApiSuggestionsConnection'),
      ],
      'fields' => fn () => [
        'suggestions' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
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
      'name' => 'Query',
      'fields' => fn () => [
        'searchSuggestions' => [
          'type' => Type::nonNull(static::type('SuggestionsConnection')),
          'description' => (string) $this->t('List of possible matching words'),
          'args' => [
            'keywords' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Keywords to search by'),
            ],
          ],
        ],
      ],
    ]);

    return $extensions;
  }
}
