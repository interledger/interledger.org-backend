<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add route resolution.
 *
 * @SchemaExtension(
 *   id = "route_schema_extension",
 *   name = "GraphQL Compose Routes",
 *   description = @Translation("URL, Links and paths"),
 *   schema = "graphql_compose"
 * )
 */
class RouteSchemaExtension extends SdlSchemaExtensionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected PathValidatorInterface $pathValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->pathValidator = $container->get('path.validator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $this->addRouteInternal($registry, $builder);
    $this->addRouteExternal($registry, $builder);
    $this->addRouteUnion($registry, $builder);

    if ($this->moduleHandler->moduleExists('redirect')) {
      $this->addRedirect($registry, $builder);
    }

    $registry->addFieldResolver(
      'Query',
      'route',
      $builder->compose(
        $builder->produce('url_or_redirect')
          ->map('path', $builder->fromArgument('path'))
          ->map('langcode', $builder->fromArgument('langcode')),

        $builder->context('langcode', $builder->fromArgument('langcode'))
      )
    );

    $registry->addTypeResolver(
      'RouteUnion',
      function ($value) {
        $type = NULL;

        if ($value instanceof Url) {
          $type = $value->isRouted() ? 'RouteInternal' : 'RouteExternal';
        }

        // Give opportunity to extend this union.
        $this->moduleHandler->invokeAll('graphql_compose_routes_union_alter', [
          $value,
          &$type,
        ]);

        if ($type) {
          return $type;
        }

        throw new UserError('Could not resolve route type.');
      });

  }

  /**
   * Add internal routes to the registry.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addRouteInternal(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver(
      'RouteInternal',
      'url',
      $builder->produce('url_path')
        ->map('url', $builder->fromParent()),
    );

    $registry->addFieldResolver(
      'RouteInternal',
      'entity',
      $builder->produce('route_entity_extra')
        ->map('url', $builder->fromParent())
        ->map('language', $builder->fromContext('langcode'))
    );

    $registry->addFieldResolver(
      'RouteInternal',
      'breadcrumbs',
      $builder->produce('breadcrumbs')
        ->map('url', $builder->fromParent())
    );

    $registry->addFieldResolver(
      'RouteInternal',
      'internal',
      $builder->fromValue(TRUE)
    );
  }

  /**
   * Add external routes to the registry.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addRouteExternal(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver(
      'RouteExternal',
      'url',
      $builder->produce('url_path')
        ->map('url', $builder->fromParent())
    );

    $registry->addFieldResolver(
      'RouteExternal',
      'internal',
      $builder->fromValue(FALSE)
    );
  }

  /**
   * Add redirect routes to the registry.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addRedirect(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver(
      'RouteRedirect',
      'url',
      $builder->compose(
        $builder->callback(fn ($redirect) => $redirect->getRedirectUrl()),
        $builder->produce('url_path')
          ->map('url', $builder->fromParent())
      )
    );

    $registry->addFieldResolver(
      'RouteRedirect',
      'internal',
      $builder->compose(
        $builder->callback(fn ($redirect) => $redirect->getRedirectUrl()),
        $builder->callback(fn ($url) => $url->isRouted())
      )
    );

    $registry->addFieldResolver(
      'RouteRedirect',
      'status',
      $builder->callback(fn ($redirect) => $redirect->getStatusCode())
    );

    $registry->addFieldResolver(
      'RouteRedirect',
      'redirect',
      $builder->fromValue(TRUE)
    );
  }

  /**
   * Resolve union type for Routed results.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   *
   * @throws \GraphQL\Error\UserError
   *   If the entity type is not exposed.
   */
  protected function addRouteUnion(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addTypeResolver(
      'RouteEntityUnion',
      function (EntityInterface $value) {
        // Find the enabled entity type plugin.
        $entity_type = $this->gqlEntityTypeManager->getPluginInstance($value->getEntityTypeId());

        if ($bundle = $entity_type->getBundle($value->bundle())) {
          return $bundle->getTypeSdl();
        }

        // Its not a 404 but its not exposed.
        throw new UserError('Entity type is not able to be loaded by route.');
      }
    );
  }

}
