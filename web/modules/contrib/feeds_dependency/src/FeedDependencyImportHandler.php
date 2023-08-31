<?php

namespace Drupal\feeds_dependency;

use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\FeedImportHandler;
use Drupal\feeds\FeedInterface;

/**
 * Runs the actual import on a feed.
 */
class FeedDependencyImportHandler extends FeedImportHandler {

  use FeedDependencyTrait;

  /**
   * Imports the whole feed at once.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import for.
   *
   * @throws \Exception
   *   In case of an error.
   */
  public function import(FeedInterface $feed) {
    $feed_dependencies = $this->getFeedDependencies($feed);
    foreach ($feed_dependencies as $feed_dependency) {
      if ($this->feedsNotSame($feed, $feed_dependency)) {
        $this->import($feed_dependency);
      }
    }

    parent::import($feed);
  }

  /**
   * {@inheritdoc}
   */
  public function startBatchImport(FeedInterface $feed) {
    // With a batch the latest task added are run first.
    // So the dependencies are added in last.
    parent::startBatchImport($feed);

    /** @var \Drupal\feeds\FeedInterface $feed_dependency */
    $feed_dependencies = $this->getFeedDependencies($feed);
    foreach ($feed_dependencies as $feed_dependency) {
      if ($this->feedsNotSame($feed, $feed_dependency)) {
        $this->startBatchImport($feed_dependency);
      }
    }
  }

  /**
   * Starts importing a feed via cron.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to queue.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   Thrown if a feed is locked.
   */
  public function startCronImport(FeedInterface $feed) {
    parent::startCronImport($feed);

    $feed_dependencies = $this->getFeedDependencies($feed);
    foreach ($feed_dependencies as $feed_dependency) {
      if ($this->feedsNotSame($feed, $feed_dependency)) {
        $this->startCronImport($feed_dependency);
      }
    }
  }

  /**
   * Handles a push import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed receiving the push.
   * @param string $payload
   *   The feed contents.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   (optional) The file system service.
   *
   * @todo Move this to a queue.
   */
  public function pushImport(FeedInterface $feed, $payload, FileSystemInterface $file_system = NULL) {
    parent::pushImport($feed, $payload);

    $feed_dependencies = $this->getFeedDependencies($feed);
    foreach ($feed_dependencies as $feed_dependency) {
      if ($this->feedsNotSame($feed, $feed_dependency)) {
        $this->pushImport($feed_dependency, $payload, $file_system);
      }
    }
  }

}
