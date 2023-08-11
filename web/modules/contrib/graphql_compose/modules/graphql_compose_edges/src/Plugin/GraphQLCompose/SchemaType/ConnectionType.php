<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Plugin\GraphQLCompose\SchemaType;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\graphql_compose_edges\EnabledBundlesTrait;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Connection",
 * )
 */
class ConnectionType extends GraphQLComposeSchemaTypeBase implements ContainerFactoryPluginInterface {

  use EnabledBundlesTrait;

  /**
   * Drupal language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A paginated set of results.'),
      'fields' => fn() => [
        'edges' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('Edge')))),
          'description' => (string) $this->t('The edges of this connection.'),
        ],
        'nodes' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('EdgeNode')))),
          'description' => (string) $this->t('The nodes of the edges of this connection.'),
        ],
        'pageInfo' => [
          'type' => Type::nonNull(static::type('ConnectionPageInfo')),
          'description' => (string) $this->t('Information to aid in pagination.'),
        ],
      ],
    ]);

    foreach ($this->getEnabledBundlePlugins() as $bundle) {
      $type_sdl = $bundle->getTypeSdl();

      $types[] = new ObjectType([
        'name' => $type_sdl . 'Connection',
        'description' => (string) $this->t('A paginated set of results for @bundle.', [
          '@bundle' => $type_sdl,
        ]),
        'interfaces' => fn() => [
          static::type('Connection'),
        ],
        'fields' => fn() => [
          'edges' => Type::nonNull(Type::listOf(Type::nonNull(static::type($type_sdl . 'Edge')))),
          'nodes' => Type::nonNull(Type::listOf(Type::nonNull(static::type($type_sdl)))),
          'pageInfo' => Type::nonNull(static::type('ConnectionPageInfo')),
        ],
      ]);
    }

    return $types;
  }

  /**
   * {@inheritdoc}
   *
   * Create bundle queries.
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    foreach ($this->getEnabledBundlePlugins() as $bundle) {

      $definition = $bundle->entityTypePlugin->getPluginDefinition();

      // Some extensions may opt to put the connection elsewhere.
      // How they do that is up to that extension.
      $query_enabled = $definition['edges_query'] ?? TRUE;
      if (!$query_enabled) {
        continue;
      }

      $type_sdl = $bundle->getTypeSdl();

      $extensions[] = new ObjectType([
        'name' => 'Query',
        'fields' => fn() => [
          $bundle->getNamePluralSdl() => [
            'type' => Type::nonNull(static::type($type_sdl . 'Connection')),
            'description' => (string) $this->t('List of all @bundle on the platform.', [
              '@bundle' => $type_sdl,
            ]),
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
              'reverse' => [
                'type' => Type::boolean(),
                'defaultValue' => FALSE,
                'description' => (string) $this->t('Reverse the order of the underlying list.'),
              ],
              'sortKey' => [
                'type' => static::type('ConnectionSortKeys'),
                'description' => (string) $this->t('Sort the underlying list by the given key.'),
              ],
              'langcode' => [
                'type' => Type::string(),
                'description' => (string) $this->t('Filter the results by language. Eg en, ja, fr.'),
                'defaultValue' => $this->languageManager->getDefaultLanguage()->getId(),
              ],
            ],
          ],
        ],
      ]);
    }

    return $extensions;
  }

}
