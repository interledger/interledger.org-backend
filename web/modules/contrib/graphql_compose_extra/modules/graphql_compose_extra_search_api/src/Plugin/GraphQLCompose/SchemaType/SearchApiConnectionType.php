<?php

namespace Drupal\graphql_compose_extra_search_api\Plugin\GraphQLCompose\SchemaType;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "SearchApiNodeConnection",
 * )
 */
class SearchApiConnectionType extends GraphQLComposeSchemaTypeBase implements ContainerFactoryPluginInterface
{

  /**
   * Drupal language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->languageManager = $container->get('language_manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A paginated set of results.'),
      'fields' => fn () => [
        'edges' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('Edge')))),
          'description' => (string) $this->t('The edges of this connection.'),
        ],
        'nodes' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('NodeUnion')))),
          'description' => (string) $this->t('The nodes of the edges of this connection.'),
        ],
        'pageInfo' => [
          'type' => Type::nonNull(static::type('ConnectionPageInfo')),
          'description' => (string) $this->t('Information to aid in pagination.'),
        ],
      ],
    ]);

    $types[] = new ObjectType([
      'name' => 'SearchApiConnection',
      'description' => (string) $this->t('A paginated set of results for Nodes'),
      'interfaces' => fn () => [
        static::type('SearchApiNodeConnection'),
      ],
      'fields' => fn () => [
        'edges' => Type::nonNull(Type::listOf(Type::nonNull(static::type('Edge')))),
        'nodes' => Type::nonNull(Type::listOf(Type::nonNull(static::type('NodeUnion')))),
        'pageInfo' => Type::nonNull(static::type('ConnectionPageInfo')),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   *
   * Create bundle queries.
   */
  public function getExtensions(): array
  {
    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Query',
      'fields' => fn () => [
        'search' => [
          'type' => Type::nonNull(static::type('SearchApiConnection')),
          'description' => (string) $this->t('List of all Nodes on the platform. Results are access controlled.'),
          'args' => [
            'after' => [
              'type' => static::type('Cursor'),
              'description' => (string) $this->t('Returns the elements that come after the specified cursor.'),
            ],
            'before' => [
              'type' => static::type('Cursor'),
              'description' => (string) $this->t('Returns the elements that come before the specified cursor.'),
            ],
            'first' => [
              'type' => Type::int(),
              'description' => (string) $this->t('Returns up to the first n elements from the list.'),
            ],
            'last' => [
              'type' => Type::int(),
              'description' => (string) $this->t('Returns up to the last n elements from the list.'),
            ],
            'keywords' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Keywords to search by'),
            ],
            'langcode' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Filter the results by language. Eg en, ja, fr.'),
              'defaultValue' => $this->languageManager->getDefaultLanguage()->getId(),
            ],
            'searchIndex' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Search index to use'),
            ],
          ],
        ],
      ],
    ]);

    return $extensions;
  }
}
