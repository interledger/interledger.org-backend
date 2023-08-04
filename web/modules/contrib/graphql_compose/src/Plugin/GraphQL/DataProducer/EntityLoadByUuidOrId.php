<?php

namespace Drupal\graphql_compose\Plugin\GraphQL\DataProducer;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\DataProducerPluginManager;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads an entity by UUID or ID if allowed.
 *
 * @DataProducer(
 *   id = "entity_load_by_uuid_or_id",
 *   name = @Translation("Load entity by uuid or ID"),
 *   description = @Translation("Loads a single entity by uuid or ID."),
 *   produces = @ContextDefinition("entity",
 *     label = @Translation("Entity")
 *   ),
 *   consumes = {
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Entity type")
 *     ),
 *     "identifier" = @ContextDefinition("string",
 *       label = @Translation("Unique identifier")
 *     ),
 *     "language" = @ContextDefinition("string",
 *       label = @Translation("Entity language"),
 *       required = FALSE
 *     ),
 *     "bundles" = @ContextDefinition("string",
 *       label = @Translation("Entity bundle(s)"),
 *       multiple = TRUE,
 *       required = FALSE
 *     ),
 *     "access" = @ContextDefinition("boolean",
 *       label = @Translation("Check access"),
 *       required = FALSE,
 *       default_value = TRUE
 *     ),
 *     "access_user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       required = FALSE,
 *       default_value = NULL
 *     ),
 *     "access_operation" = @ContextDefinition("string",
 *       label = @Translation("Operation"),
 *       required = FALSE,
 *       default_value = "view"
 *     )
 *   }
 * )
 */
class EntityLoadByUuidOrId extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new EntityLoadByUuidOrId instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginManager $pluginManager
   *   The data producer plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected DataProducerPluginManager $pluginManager,
    protected ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.graphql.data_producer'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(...$args): ?Deferred {

    // Users select how they want to load entities.
    $expose_entity_ids = $this->configFactory
      ->get('graphql_compose.settings')
      ->get('settings.expose_entity_ids');

    $plugin_id = 'entity_load_by_uuid';

    if ($expose_entity_ids && !Uuid::isValid($args[1])) {
      $plugin_id = 'entity_load';
    }

    return $this->pluginManager
      ->createInstance($plugin_id, $this->configuration)
      ->resolve(...$args);
  }

}
