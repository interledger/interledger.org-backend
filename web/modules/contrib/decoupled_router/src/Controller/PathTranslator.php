<?php

namespace Drupal\decoupled_router\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\decoupled_router\PathTranslatorEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Controller that receives the path to inspect.
 */
class PathTranslator extends ControllerBase {

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * EventInfoController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, HttpKernelInterface $http_kernel) {
    $this->eventDispatcher = $event_dispatcher;
    $this->httpKernel = $http_kernel;
  }

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('http_kernel')
    );
  }

  /**
   * Responds with all the information about the path.
   */
  public function translate(Request $request) {
    $path = $request->query->get('path');
    if (empty($path)) {
      throw new NotFoundHttpException('Unable to translate empty path. Please send a ?path query string parameter with your request.');
    }
    // Now that we have the path, let's fire an event for translations.
    // @deprecated since symfony/http-kernel 5.3, use MAIN_REQUEST instead.
    // To ease the migration, this constant won't be removed until Symfony 7.0.
    $event = new PathTranslatorEvent(
      $this->httpKernel,
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      sprintf('/%s', ltrim($path, '/'))
    );
    // Event subscribers are in charge of setting the appropriate response,
    // including cacheability metadata.
    $this->eventDispatcher->dispatch($event, PathTranslatorEvent::TRANSLATE);
    /** @var \Drupal\Core\Cache\CacheableJsonResponse $response */
    $response = $event->getResponse();
    $response->headers->add(['Content-Type' => 'application/json']);
    $response->getCacheableMetadata()->addCacheContexts([
      'url.query_args:path',
      'languages:' . LanguageInterface::TYPE_CONTENT,
    ]);
    if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
      $response->getCacheableMetadata()->addCacheTags(['4xx-response']);
    }
    return $response;
  }

}
