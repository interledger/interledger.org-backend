<?php

namespace Drupal\graphql_compose_extra_search_api\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_extra_search_api\SearchApiPluginSuggestionsHelper;
use Drupal\graphql_compose_extra_search_api\SuggestionConnection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queries nodes on the platform.
 *
 * @DataProducer(
 *   id = "graphql_compose_extra_search_api_suggestions",
 *   name = @Translation("Get search suggestions"),
 *   description = @Translation("Fetches suggestions"),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Array of words")
 *   ),
 *   consumes = {
 *     "keywords" = @ContextDefinition("string",
 *       label = @Translation("Keywords"),
 *       required = TRUE
 *     )
 *   },
 * )
 */
class SearchApiPluginSuggestions extends DataProducerPluginBase implements ContainerFactoryPluginInterface
{

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('graphql.buffer.entity'),
      $container->get('graphql.buffer.entity_uuid'),
      $container->get('graphql.buffer.entity_revision')
    );
  }

  /**
   * EntityLoad constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphqlEntityBuffer
   *   The GraphQL entity buffer.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer $graphqlEntityUuidBuffer
   *   The GraphQL entity uuid buffer.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer $graphqlEntityRevisionBuffer
   *   The GraphQL entity revision buffer.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityBuffer $graphqlEntityBuffer,
    protected EntityUuidBuffer $graphqlEntityUuidBuffer,
    protected EntityRevisionBuffer $graphqlEntityRevisionBuffer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->graphqlEntityBuffer = $graphqlEntityBuffer;
    $this->graphqlEntityUuidBuffer = $graphqlEntityUuidBuffer;
    $this->graphqlEntityRevisionBuffer = $graphqlEntityRevisionBuffer;
  }

  /**
   * Resolves the request to the requested values.
   *
   * @param string $keywords
   *   Search keywords.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return \Drupal\graphql_compose\GraphQL\ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(string $keywords, RefinableCacheableDependencyInterface $metadata)
  {

    $query_helper = new SearchApiPluginSuggestionsHelper(
      $keywords,
      $this->entityTypeManager,
      $this->graphqlEntityBuffer,
    );

    $metadata->addCacheableDependency($query_helper);

    $connection = new SuggestionConnection($query_helper);

    return $connection->suggestions();
  }
}
