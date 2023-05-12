<?php

namespace Drupal\decoupled_router;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Path translation event.
 *
 * We don't use GetResponseEvent because we want to initialize the response
 * without stopping propagation.
 */
class PathTranslatorEvent extends KernelEvent {

  use StringTranslationTrait;

  const TRANSLATE = 'decoupled_router.translate_path';

  /**
   * The response.
   *
   * @var \Drupal\Core\Cache\CacheableJsonResponse
   */
  private $response;

  /**
   * The path that needs translation.
   *
   * @var string
   */
  protected $path;

  /**
   * PathTranslatorEvent constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The kernel.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param int $requestType
   *   The type of request: master or subrequest.
   * @param string $path
   *   The path to process.
   */
  public function __construct(HttpKernelInterface $kernel, Request $request, $requestType, $path) {
    parent::__construct($kernel, $request, $requestType);
    $this->path = $path;

    // Assume a 404 from start.
    $this->response = new CacheableJsonResponse(
      [
        'message' => $this->t(
          'Unable to resolve path @path.',
          ['@path' => $path]
        ),
        'details' => $this->t(
          'None of the available methods were able to find a match for this path.'
        ),
      ],
      404
    );
  }

  /**
   * Get the path.
   *
   * @return string
   *   The path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Set the path.
   *
   * @param string $path
   *   The path.
   */
  public function setPath($path) {
    $this->path = $path;
  }

  /**
   * Returns the response object.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The response.
   */
  public function getResponse() {
    return $this->response;
  }

}
