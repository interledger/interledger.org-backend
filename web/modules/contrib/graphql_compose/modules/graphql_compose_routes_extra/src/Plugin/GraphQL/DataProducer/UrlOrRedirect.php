<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes_extra\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load a Route or Redirect based on Path.
 *
 * @DataProducer(
 *   id = "url_or_redirect",
 *   name = @Translation("Load Url or Redirect"),
 *   description = @Translation("Loads a Url or Redirect by the path."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Route or Redirect")
 *   ),
 *   consumes = {
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Path"),
 *       required = FALSE
 *     ),
 *     "langcode" = @ContextDefinition("string",
 *       label = @Translation("Language code"),
 *       required = FALSE
 *     ),
 *     "url" = @ContextDefinition("any",
 *       label = @Translation("URL"),
 *       required = FALSE
 *     ),
 *     "domain" = @ContextDefinition("string",
 *       label = @Translation("Domain"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class UrlOrRedirect extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('redirect.repository', ContainerInterface::NULL_ON_INVALID_REFERENCE),
    );
  }

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   Drupal alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   Drupal path validator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Drupal module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Drupal language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal renderer.
   * @param \Drupal\redirect\RedirectRepository|null $redirectRepository
   *   Redirect module repository.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AliasManagerInterface $aliasManager,
    protected PathValidatorInterface $pathValidator,
    protected ModuleHandlerInterface $moduleHandler,
    protected LanguageManagerInterface $languageManager,
    protected RendererInterface $renderer,
    protected $redirectRepository = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Resolver.
   *
   * @return null|Redirect|Url
   *   Path resolution result.
   */
  public function resolve(?string $path, ?string $langcode, ?Url $url, ?string $domain, RefinableCacheableDependencyInterface $metadata) {

    if (!empty($url)) {
      if (!$this->hasLink($url) || !$this->isAccessible($url)) {
        return $this->fail($metadata);
      }

      // This seems dumb. Turn url back into a path.
      $context = new RenderContext();
      $path = $this->renderer->executeInRenderContext($context, function () use ($url): string {
        return $url->toString();
      });
    }

    // Give opportunity for other modules to alter incoming path urls.
    $this->moduleHandler->invokeAll('graphql_compose_routes_extra_incoming_alter', [&$path]);

    if ($redirect = $this->getRedirect($path, $langcode)) {
      return $redirect;
    }

    // Check the aliases by language.
    if (str_starts_with($path, '/')) {
      $path = $this->aliasManager->getPathByAlias($path, $langcode);
    }

    // Convert path string to a url.
    $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($path);

    $domain = \Drupal::service('domain.negotiator')->getActiveDomain();
    $metadata->addCacheableDependency($url);
    $metadata->addCacheableDependency($domain);

    // Reconstruct the url, path may have been altered.
    if (!$url) {
      return $this->fail($metadata);
    }

    if (!$this->hasLink($url) || !$this->isAccessible($url)) {
      return $this->fail($metadata);
    }

    return $url;
  }

  /**
   * Get the URL for a redirect.
   */
  protected function getRedirect(string $path, ?string $langcode): mixed {
    // Add default language for redirect check.
    if (!$langcode) {
      // Redirect module would seem to always default to the current language.
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
    }

    // Check redirects.
    $redirect = $this->redirectRepository
      ? $this->redirectRepository->findMatchingRedirect($path, [], $langcode)
      : NULL;

    if (!$redirect) {
      return NULL;
    }

    $url = $redirect->getRedirectUrl();

    if ($this->hasLink($url) && $this->isAccessible($url)) {
      return $redirect;
    }

    return NULL;
  }

  /**
   * Check if the URL shouldnt actually be a route.
   */
  protected function hasLink(Url $url): bool {
    return $url->isRouted() ? $url->getRouteName() !== '<nolink>' : TRUE;
  }

  /**
   * Check if the URL shouldnt actually be a route.
   */
  protected function isAccessible(Url $url): bool {
    return $url->isRouted() ? $url->access() : TRUE;
  }

  /**
   * Cache the response and exit.
   */
  protected function fail($metadata) {
    $metadata->addCacheTags(['4xx-response']);
    return NULL;
  }

}
