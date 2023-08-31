<?php

namespace Drupal\feeds_dependency;

use Drupal\feeds\FeedInterface;

/**
 * Provides a trait for accessing to feed set as dependency.
 */
trait FeedDependencyTrait {

  /**
   * Get the feeds set as dependency.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   *
   * @return mixed \Drupal\feeds\FeedInterface|[]
   *   An array of Feed objects.
   */
  protected function getFeedDependencies(FeedInterface $feed) {
    $feed_dependencies = $feed->get('feed_dependency_id')->referencedEntities();
    if ($feed_dependencies) {
      return $feed_dependencies;
    }
    return [];
  }

  /**
   * Check two feeds are not the same.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   * @param \Drupal\feeds\FeedInterface $feed_dependency
   *   The feed object.
   *
   * @return bool
   *   Return TRUE if feeds are not the same.
   */
  protected function feedsNotSame(FeedInterface $feed, FeedInterface $feed_dependency) {
    if ($feed->id() != $feed_dependency->id()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * The main feed should clear too the feed set as dependency.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   *
   * @return bool
   *   Return TRUE if the feed should clear the feed set as dependency.
   */
  protected function clearFeedDependency(FeedInterface $feed) {
    return (bool) $feed->get('clear_dependency')->value;
  }

}
