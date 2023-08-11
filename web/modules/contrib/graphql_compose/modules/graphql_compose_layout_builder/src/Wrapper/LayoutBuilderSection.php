<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder\Wrapper;

use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * A wrapped layout builder section.
 *
 * We wrap in the storage and display delta.
 */
class LayoutBuilderSection {

  /**
   * Construct a section wrapper.
   *
   * @var \Drupal\layout_builder\Section $section
   *  The section.
   * @var \Drupal\layout_builder\SectionStorageInterface $storage
   *   The section storage.
   * @var int $delta
   *  The section delta.
   */
  public function __construct(
    protected Section $section,
    protected SectionStorageInterface $storage,
    protected int $delta
  ) {}

  /**
   * The section storage id.
   *
   * @return string
   *   The section storage id.
   */
  public function id(): string {
    $parts = [
      $this->storage->getStorageId(),
      $this->storage->getStorageType(),
      'section',
      $this->delta,
    ];
    return implode('.', $parts);
  }

  /**
   * The display position of this section.
   *
   * @return int
   *   The section delta.
   */
  public function getDelta(): int {
    return $this->delta;
  }

  /**
   * The section storage.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage.
   */
  public function getStorage(): SectionStorageInterface {
    return $this->storage;
  }

  /**
   * Fwd properties to the section.
   *
   * @param string $property
   *   The property name.
   *
   * @return mixed
   *   The property value.
   */
  public function __get(string $property) {
    return $this->section->{$property};
  }

  /**
   * Fwd methods to the section.
   *
   * @param string $method
   *   The method name.
   * @param array $arguments
   *   The method arguments.
   *
   * @return mixed
   *   The method return value.
   */
  public function __call($method, $arguments) {
    return $this->section->{$method}(...$arguments);
  }

}
