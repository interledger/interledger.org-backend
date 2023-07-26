<?php

namespace Drupal\graphql_compose_extra_edges\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_extra_edges\EntityConnection;
use Drupal\graphql_compose_extra_edges\EntityTypePluginQueryHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queries nodes on the platform.
 *
 * @DataProducer(
 *   id = "graphql_compose_extra_edges_node",
 *   name = @Translation("Query a list of nodes"),
 *   description = @Translation("Loads the node entities."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("NodesConnection")
 *   ),
 *   consumes = {
 *     "first" = @ContextDefinition("integer",
 *       label = @Translation("First"),
 *       required = FALSE
 *     ),
 *     "after" = @ContextDefinition("string",
 *       label = @Translation("After"),
 *       required = FALSE
 *     ),
 *     "last" = @ContextDefinition("integer",
 *       label = @Translation("Last"),
 *       required = FALSE
 *     ),
 *     "before" = @ContextDefinition("string",
 *       label = @Translation("Before"),
 *       required = FALSE
 *     ),
 *     "reverse" = @ContextDefinition("boolean",
 *       label = @Translation("Reverse"),
 *       required = FALSE,
 *     ),
 *     "sortKey" = @ContextDefinition("string",
 *       label = @Translation("Sort key"),
 *       required = FALSE
 *     ),
 *     "langcode" = @ContextDefinition("string",
 *       label = @Translation("Language code"),
 *       required = FALSE
 *     ),
 *     "filter" = @ContextDefinition("any",
 *       label = @Translation("Filter"),
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class NodePluginEdge extends DataProducerPluginBase implements ContainerFactoryPluginInterface
{

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity buffer service.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected EntityBuffer $graphqlEntityBuffer;

  /**
   * Drupal language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->graphqlEntityBuffer = $container->get('graphql.buffer.entity');
    $instance->languageManager = $container->get('language_manager');
    $instance->currentUser = $container->get('current_user');

    return $instance;
  }

   /**
   * Resolves the request to the requested values.
   *
   * @param int|null $first
   *   Fetch the first X results.
   * @param string|null $after
   *   Cursor to fetch results after.
   * @param int|null $last
   *   Fetch the last X results.
   * @param string|null $before
   *   Cursor to fetch results before.
   * @param bool|null $reverse
   *   Reverses the order of the data.
   * @param string|null $sortKey
   *   Key to sort by.
   * @param string|null $langcode
   *   Language code to filter with.
   * @param array|null $filter
   *   Query filter.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return \Drupal\graphql_compose\GraphQL\ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(?int $first, ?string $after, ?int $last, ?string $before, ?bool $reverse, ?string $sortKey, ?string $langcode, ?array $filter, RefinableCacheableDependencyInterface $metadata) {

    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage()->getId();

    $query_helper = new EntityTypePluginQueryHelper(
      $sortKey,
      $filter,
      $langcode,
      'node',
      null,
      $this->entityTypeManager,
      $this->graphqlEntityBuffer,
    );

    $connection = new EntityConnection($query_helper);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    $connection->setAccessAccount($this->currentUser);
    $connection->setCacheContext($metadata);

    return $connection;
  }
}
