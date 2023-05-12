<?php

namespace Drupal\decoupled_router\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\decoupled_router\PathTranslatorEvent;
use Drupal\path_alias\AliasManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Route;

/**
 * Event subscriber that processes a path translation with the router info.
 */
class RouterPathTranslatorSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $router;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The langcode if added as a prefix to the path.
   *
   * @var string
   */
  protected $langcode;

  /**
   * RouterPathTranslatorSubscriber constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $router
   *   The router.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The alias manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(
    ContainerInterface $container,
    LoggerInterface $logger,
    UrlMatcherInterface $router,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    AliasManagerInterface $aliasManager,
    LanguageManagerInterface $language_manager,
    EntityRepositoryInterface $entity_repository
  ) {
    $this->container = $container;
    $this->logger = $logger;
    $this->router = $router;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->aliasManager = $aliasManager;
    $this->languageManager = $language_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Processes a path translation request.
   */
  public function onPathTranslation(PathTranslatorEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof CacheableJsonResponse) {
      $this->logger->error('Unable to get the response object for the decoupled router event.');
      return;
    }
    $path = $event->getPath();
    $path = $this->cleanSubdirInPath($path, $event->getRequest());
    if ($this->languageManager->isMultilingual()) {
      $path = $this->getPathFromAlias($path);
    }

    try {
      $match_info = $this->router->match($path);
    }
    catch (ResourceNotFoundException $exception) {
      // If URL is external, we won't perform checks for content in Drupal,
      // but assume that it's working.
      if (UrlHelper::isExternal($path)) {
        $response->setStatusCode(200);
        $response->setData([
          'resolved' => $path,
        ]);
      }
      return;
    }
    catch (MethodNotAllowedException $exception) {
      $response->setStatusCode(403);
      return;
    }
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    /** @var bool $param_uses_uuid */
    [
      $entity,
      $param_uses_uuid,
      $route_parameter_entity_key,
    ] = $this->findEntityAndKeys($match_info);
    if (!$entity) {
      $this->logger->notice('A route has been found but it has no entity information.');
      return;
    }
    elseif (!empty($this->langcode)) {
      if ($entity->hasTranslation($this->langcode) && method_exists($entity, 'getTranslation')) {
        $entity = $entity->getTranslation($this->langcode);
      }
      else {
        $entity = $this->entityRepository->getTranslationFromContext($entity, $this->langcode);
      }
    }
    $response->addCacheableDependency($entity);
    if ($entity->getEntityType() instanceof ContentEntityType) {
      $can_view = $entity->access('view', NULL, TRUE);
      if (!$can_view->isAllowed()) {
        $response->setData([
          'message' => 'Access denied for entity.',
          'details' => 'This user does not have access to view the resolved entity. Please authenticate and try again.',
        ]);
        $response->setStatusCode(403);
        $response->addCacheableDependency($can_view);
        return;
      }
    }

    $entity_type_id = $entity->getEntityTypeId();
    $canonical_url = NULL;
    try {
      $canonical_url = $entity->toUrl('canonical', ['absolute' => TRUE])->toString(TRUE);
    }
    catch (EntityMalformedException $e) {
      $response->setData([
        'message' => 'Unable to build entity URL.',
        'details' => 'A valid entity was found but it was impossible to generate a valid canonical URL for it.',
      ]);
      $response->setStatusCode(500);
      watchdog_exception('decoupled_router', $e);
      return;
    }
    $entity_param = $param_uses_uuid ? $entity->id() : $entity->uuid();
    $resolved_url = Url::fromRoute(
      $match_info[RouteObjectInterface::ROUTE_NAME],
      [$route_parameter_entity_key => $entity_param],
      [
        'absolute' => TRUE,
        'language' => $entity->language()
      ]
    )->toString(TRUE);
    $response->addCacheableDependency($canonical_url);
    $response->addCacheableDependency($resolved_url);
    $is_home_path = $this->resolvedPathIsHomePath($resolved_url->getGeneratedUrl());
    $response->addCacheableDependency(
      (new CacheableMetadata())->setCacheContexts(['url.path.is_front'])
    );

    $label_accessible = $entity->access('view label', NULL, TRUE);
    $response->addCacheableDependency($label_accessible);
    $langcode = NULL;
    if ($entity instanceof TranslatableInterface) {
      $langcode = $entity->language()->getId();
    }
    $output = [
      'resolved' => $resolved_url->getGeneratedUrl(),
      'isHomePath' => $is_home_path,
      'entity' => [
        'canonical' => $canonical_url->getGeneratedUrl(),
        'type' => $entity_type_id,
        'bundle' => $entity->bundle(),
        'id' => $entity->id(),
        'uuid' => $entity->uuid(),
        'langcode' => $langcode,
      ],
    ];
    if ($label_accessible->isAllowed()) {
      $output['label'] = $entity->label();
    }
    // Allow to alter basic router info.
    $this->moduleHandler->invokeAll('decoupled_router_info_alter', [
      &$output, ['entity' => $entity],
    ]);

    // If the route is JSON API, it means that JSON API is installed and its
    // services can be used.
    if ($this->moduleHandler->moduleExists('jsonapi')) {
      /** @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $rt_repo */
      $rt_repo = $this->container->get('jsonapi.resource_type.repository');
      $rt = $rt_repo->get($entity_type_id, $entity->bundle());
      $type_name = $rt->getTypeName();
      $jsonapi_base_path = Url::fromRoute(
        'jsonapi.resource_list',
        [],
        ['language' => $entity->language()]
      )->toString(TRUE);
      $entry_point_url = Url::fromRoute(
        'jsonapi.resource_list',
        [],
        [
          'absolute' => TRUE,
          'language' => $entity->language(),
        ]
      )->toString(TRUE);
      $route_name = sprintf('jsonapi.%s.individual', $type_name);
      $individual = Url::fromRoute(
        $route_name,
        [
          static::getEntityRouteParameterName($route_name, $entity_type_id) => $entity->uuid(),
        ],
        [
          'absolute' => TRUE,
          'language' => $entity->language(),
        ]
      )->toString(TRUE);
      $response->addCacheableDependency($entry_point_url);
      $response->addCacheableDependency($individual);
      $output['jsonapi'] = [
        'individual' => $individual->getGeneratedUrl(),
        'resourceName' => $type_name,
        'pathPrefix' => trim($jsonapi_base_path->getGeneratedUrl(), '/'),
        'basePath' => $jsonapi_base_path->getGeneratedUrl(),
        'entryPoint' => $entry_point_url->getGeneratedUrl(),
      ];
      $output['meta'] = [
        'deprecated' => [
          'jsonapi.pathPrefix' => $this->t(
            'This property has been deprecated and will be removed in the next version of Decoupled Router. Use @alternative instead.', ['@alternative' => 'basePath']
          ),
        ],
      ];
    }
    $response->addCacheableDependency($entity);
    $response->setStatusCode(200);
    $response->setData($output);

    $event->stopPropagation();
  }

  /**
   * Get the underlying entity and the type of ID param enhancer for the routes.
   *
   * @param array $match_info
   *   The router match info.
   *
   * @return array
   *   The pair of \Drupal\Core\Entity\EntityInterface and bool with the
   *   underlying entity and the info weather or not it uses UUID for the param
   *   enhancement. It also returns the name of the parameter under which the
   *   entity lives in the route ('node' vs 'entity').
   */
  protected function findEntityAndKeys(array $match_info) {
    $entity = NULL;
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $match_info[RouteObjectInterface::ROUTE_OBJECT];
    $route_parameters = $route->getOption('parameters');
    $route_parameter_entity_key = 'entity';
    if (
      !empty($match_info['entity']) &&
      $match_info['entity'] instanceof EntityInterface
    ) {
      $entity = $match_info['entity'];
    }
    else {
      $entity_type_id = $this->findEntityTypeFromRoute($route);
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      // @todo $match_info[$entity_type_id] is broken for JSON API 2.x routes.
      // Now it will be $match_info[$entity_type_id] for core and
      // $match_info['entity'] for JSON API :-(.
      if (
        !empty($entity_type_id) &&
        !empty($match_info[$entity_type_id]) &&
        $match_info[$entity_type_id] instanceof EntityInterface
      ) {
        $route_parameter_entity_key = $entity_type_id;
        $entity = $match_info[$entity_type_id];
      }
    }
    $param_uses_uuid = strpos(
      $route_parameters[$route_parameter_entity_key]['converter'],
      'entity_uuid'
    ) === FALSE;

    return [$entity, $param_uses_uuid, $route_parameter_entity_key];
  }

  /**
   * Computes the name of the entity route parameter for JSON API routes.
   *
   * @param string $route_name
   *   A JSON API route name.
   * @param string $entity_type_id
   *   The corresponding entity type ID.
   *
   * @return string
   *   Either 'entity' or $entity_type_id.
   *
   * @todo Remove this once decoupled_router requires jsonapi >= 8.x-2.0.
   */
  protected static function getEntityRouteParameterName($route_name, $entity_type_id) {
    static $first;

    if (!isset($first)) {
      $route_parameters = \Drupal::service('router.route_provider')
        ->getRouteByName($route_name)
        ->getOption('parameters');
      $first = isset($route_parameters['entity'])
        ? 'entity'
        : $entity_type_id;
      return $first;
    }

    return $first === 'entity'
      ? 'entity'
      : $entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PathTranslatorEvent::TRANSLATE][] = ['onPathTranslation'];
    return $events;
  }

  /**
   * Extracts the entity type for the route parameters.
   *
   * If there are more than one parameter, this function will return the first
   * one.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   *
   * @return string|null
   *   The entity type ID or NULL if not found.
   */
  protected function findEntityTypeFromRoute(Route $route) {
    $parameters = (array) $route->getOption('parameters');
    // Find the entity type for the first parameter that has one.
    return array_reduce($parameters, function ($carry, $parameter) {
      if (!$carry && !empty($parameter['type'])) {
        $parts = explode(':', $parameter['type']);
        // We know that the parameter is for an entity if the type is set to
        // 'entity:<entity-type-id>'.
        if ($parts[0] === 'entity' && !empty($parts[1])) {
          $carry = $parts[1];
        }
      }
      return $carry;
    }, NULL);
  }

  /**
   * Removes the subdir prefix from the path.
   *
   * @param string $path
   *   The path that can contain the subdir prefix.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to extract the path prefix from.
   *
   * @return string
   *   The clean path.
   */
  protected function cleanSubdirInPath($path, Request $request) {
    // Remove any possible leading subdir information in case Drupal is
    // installed under http://example.com/d8/index.php
    $regexp = preg_quote($request->getBasePath(), '/');
    return preg_replace(sprintf('/^%s/', $regexp), '', $path);
  }

  /**
   * Convert an alias to its source path.
   *
   * This is a workaround for a bug where matcher fails on aliases prefixed by
   * a language prefix when it doesn't match the negotiated language.
   *
   * @param string $path
   *   The input path string.
   *
   * @return string
   *   The output path string.
   */
  protected function getPathFromAlias($path) {
    $config = $this->configFactory->get('language.negotiation')->get('url');
    $language_negotiation_url = $this->languageManager->getNegotiator()
      ->getNegotiationMethodInstance('language-url');
    $router_request = Request::create($path);
    $langcode = $language_negotiation_url->getLangcode($router_request);
    $prefix = $config['prefixes'][$langcode] ?? NULL;
    if ($prefix && ($path == "/$prefix" || strpos($path, "/$prefix/") === 0)) {
      $this->langcode = $langcode;
      $path_without_prefix = $language_negotiation_url->processInbound($path, $router_request);
      $path = $this->aliasManager->getPathByAlias($path_without_prefix, $langcode);
      $path = "/$prefix" . $path;
    }

    return $path;
  }

  /**
   * Checks if the resolved path is the home path.
   *
   * @param string $resolved_url
   *   The resolved url from the request.
   *
   * @return bool
   *   True if the resolved path is the home path, false otherwise.
   */
  protected function resolvedPathIsHomePath($resolved_url) {
    $home_path = $this->configFactory->get('system.site')->get('page.front');
    $home_url = Url::fromUserInput($home_path, ['absolute' => TRUE])->toString(TRUE)->getGeneratedUrl();

    return $resolved_url === $home_url;
  }

}
