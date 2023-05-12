<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add route resolution.
 *
 * @SchemaExtension(
 *   id = "route_schema_extension",
 *   name = "Route Schema Extension",
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
  protected $pathValidator;

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

    // Extend links with route information.
    $registry->addFieldResolver(
      'Link',
      'route',
      $builder->callback(function ($link) {
        $path = $link['url'] ?? $link['uri'] ?? NULL;
        return $path ? $this->pathValidator->getUrlIfValid($path) : NULL;
      }),
    );

    $registry->addFieldResolver(
      'Query',
      'route',
      $builder->produce('url_or_redirect')
        ->map('path', $builder->fromArgument('path'))
        ->map('langcode', $builder->fromArgument('langcode')),
    );

    $registry->addTypeResolver('RouteUnion', function ($value) {
      if ($value instanceof Url) {
        return $value->isRouted() ? 'RouteInternal' : 'RouteExternal';
      }

      if ($this->moduleHandler->moduleExists('redirect') && get_class($value) === 'Drupal\redirect\Entity\Redirect') {
        return 'RouteRedirect';
      }

      throw new \Error('Could not resolve route type.');
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
      $builder->produce('route_entity')
        ->map('url', $builder->fromParent())
    );

    $registry->addFieldResolver(
      'RouteInternal',
      'langcode',
      $builder->produce('route_language')
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
        // Just going to fail gracefully.
        return 'UnsupportedType';
      }
    );
  }

}
