<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQL\DataProducer;

use Drupal\Core\Breadcrumb\BreadcrumbManager;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_routes\GraphQL\Buffers\SubrequestBuffer;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load breadcrumbs for a URL.
 *
 * @DataProducer(
 *   id = "breadcrumbs",
 *   name = @Translation("Breadcrumbs for a route"),
 *   description = @Translation("Based on a route URL."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Breadcrumbs")
 *   ),
 *   consumes = {
 *     "url" = @ContextDefinition("any",
 *       label = @Translation("Url to build breadcrumbs from"),
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class Breadcrumbs extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Drupal language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Drupal current route match.
   * @param \Drupal\Core\Breadcrumb\BreadcrumbManager $breadcrumbManager
   *   Drupal breadcrumb manager.
   * @param \Drupal\graphql_compose\GraphQL\Buffers\SubrequestBuffer $subrequestBuffer
   *   GraphQL sub request buffer.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LanguageManagerInterface $languageManager,
    protected RendererInterface $renderer,
    protected RouteMatchInterface $routeMatch,
    protected BreadcrumbManager $breadcrumbManager,
    protected SubrequestBuffer $subrequestBuffer,
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
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('current_route_match'),
      $container->get('breadcrumb'),
      $container->get('graphql_compose_routes.buffer.subrequest'),
    );
  }

  /**
   * Resolve breadcrumbs via subrequest.
   *
   * @param \Drupal\Core\Url|null $url
   *   Url to resolve breadcrumbs for.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cache metadata.
   *
   * @return array
   *   Array of breadcrumb links.
   */
  public function resolve(?Url $url, RefinableCacheableDependencyInterface $metadata) {

    if (!$url) {
      return [];
    }

    $context = new RenderContext();

    $resolve = $this->subrequestBuffer->add(
      $url,
      function () use ($context) {
        return $this->renderer->executeInRenderContext($context, function () {
          $this->languageManager->reset();
          return $this->breadcrumbManager->build($this->routeMatch);
        });
      }
    );

    $metadata->addCacheableDependency($url);

    return new Deferred(function () use ($resolve, $metadata, $context) {

      /** @var \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumbs */
      $breadcrumbs = $resolve();

      $links = $this->renderer->executeInRenderContext($context, fn () => array_map(
        fn(Link $link) => [
          'title' => $link->getText(),
          'url' => $link->getUrl()->toString(),
          'internal' => $link->getUrl()->isRouted(),
        ],
        $breadcrumbs ? $breadcrumbs->getLinks() : []
      ));

      if (!$context->isEmpty()) {
        $metadata->addCacheableDependency($context->pop());
      }

      return $links;
    });

  }

}
