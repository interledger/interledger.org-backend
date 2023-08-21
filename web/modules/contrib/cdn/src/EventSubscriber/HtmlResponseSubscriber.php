<?php

declare(strict_types = 1);

namespace Drupal\cdn\EventSubscriber;

use Drupal\cdn\CdnSettings;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HtmlResponseSubscriber implements EventSubscriberInterface {

  /**
   * @param \Drupal\cdn\CdnSettings $settings
   *   The CDN settings service.
   */
  public function __construct(protected readonly CdnSettings $settings) {}

  /**
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event): void {
    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse) {
      return;
    }

    if (!$this->settings->isEnabled()) {
      return;
    }

    $this->addPreConnectLinkHeaders($response);
  }

  /**
   * Adds preconnect link headers to the HTML response.
   *
   * @param \Drupal\Core\Render\HtmlResponse $response
   *   The HTML response to update.
   *
   * @see https://www.w3.org/TR/resource-hints/#preconnect
   */
  protected function addPreconnectLinkHeaders(HtmlResponse $response): void {
    foreach ($this->settings->getDomains() as $domain) {
      $response->headers->set('Link', '<//' . $domain . '>; rel=preconnect; crossorigin', FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This event subscriber wants to directly manipulate the Symfony response
    // object's headers. Therefore we must run after
    // \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::processAttachments,
    // which would otherwise overwrite us. That is called by
    // \Drupal\Core\EventSubscriber\HtmlResponseSubscriber (priority 0), so
    // use a lower priority.
    $events[KernelEvents::RESPONSE][] = ['onRespond', -10];

    return $events;
  }

}
