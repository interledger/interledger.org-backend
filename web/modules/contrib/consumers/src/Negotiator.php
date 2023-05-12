<?php

namespace Drupal\consumers;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extracts the consumer information from the given context.
 *
 * @internal
 */
class Negotiator {

  use LoggerAwareTrait;

  /**
   * Protected requestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The default consumer.
   *
   * @var \Drupal\consumers\Entity\ConsumerInterface
   */
  protected $defaultConsumer;

  /**
   * Instantiates a new Negotiator object.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Obtains the consumer from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\consumers\Entity\ConsumerInterface|null
   *   The consumer.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  protected function doNegotiateFromRequest(Request $request) {
    // There are several ways to negotiate the consumer:
    // 1. Via a custom header.
    $consumer_id = $request->headers->get('X-Consumer-ID');
    if (!$consumer_id) {
      // 2. Via a query string parameter.
      $consumer_id = $request->query->get('consumerId');
      if (!$consumer_id && $request->query->has('_consumer_id')) {
        $this->logger->warning('The "_consumer_id" query string parameter is deprecated and it will be removed in the next major version of the module, please use "consumerId" instead.');
        $consumer_id = $request->query->get('_consumer_id');
      }
    }
    if ($consumer_id) {
      try {
        $results = $this->storage->loadByProperties(['client_id' => $consumer_id]);
        /** @var \Drupal\consumers\Entity\ConsumerInterface $consumer */
        $consumer = !empty($results) ? reset($results) : $results;
      }
      catch (EntityStorageException $exception) {
        watchdog_exception('consumers', $exception);
      }
    }
    if (empty($consumer)) {
      $consumer = $this->loadDefaultConsumer();
    }
    return $consumer;
  }

  /**
   * Obtains the consumer from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request object to inspect for a consumer. Set to NULL to use the
   *   current request.
   *
   * @return \Drupal\consumers\Entity\ConsumerInterface|null
   *   The consumer.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  public function negotiateFromRequest(Request $request = NULL) {
    // If the request is not provided, use the request from the stack.
    $request = $request ? $request : $this->requestStack->getCurrentRequest();
    $consumer = $this->doNegotiateFromRequest($request);
    $request->attributes->set('consumer_id', $consumer->getClientId());
    return $consumer;
  }

  /**
   * Finds and loads the default consumer.
   *
   * @return \Drupal\consumers\Entity\ConsumerInterface
   *   The consumer entity.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  protected function loadDefaultConsumer() {
    if (!empty($this->defaultConsumer)) {
      return $this->defaultConsumer;
    }

    // Find the default consumer.
    $results = $this->storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_default', TRUE)
      ->execute();
    $consumer_id = reset($results);
    if (!$consumer_id) {
      // Throw if there is no default consumer..
      throw new MissingConsumer('Unable to find the default consumer.');
    }
    $this->defaultConsumer = $this->storage->load($consumer_id);

    return $this->defaultConsumer;
  }

  /**
   * Sets the storage from the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function setEntityStorage(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('consumer');
  }

}
