<?php

namespace Drupal\next_cloudfront_purge\EventSubscriber;

use Drupal\next\Event\EntityEvents;
use Drupal\next\Event\EntityRevalidatedEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines an event subscriber for entity revalidated events.
 */
class EntityRevalidatedEventSubscriber implements EventSubscriberInterface
{

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents()
  {
    $events[EntityEvents::ENTITY_REVALIDATED] = ['onRevalidated'];
    return $events;
  }

  /**
   * Responds to entity revalidated.
   *
   * @param \Drupal\next\Event\EntityRevalidatedEventInterface $event
   *   The event.
   */
  public function onRevalidated(EntityRevalidatedEventInterface $event)
  {
    if ($event->isRevalidated()) {
      // Do something if entity has been successfully revalidated.
      $path = $event->getEntityUrl();
      cloudfront_cache_path_invalidate_url([$path == '/home' ? '/' : $path]);
    }
  }
}
