<?php

declare(strict_types=1);

namespace Drupal\graphql_compose;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * Language inflector service.
 */
class LanguageInflector {

  /**
   * Construct language inflector.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected InflectorInterface $inflector
  ) {}

  /**
   * Returns the singular forms of a string.
   *
   * If the method can't determine the form with certainty,
   * several possible singulars are returned.
   *
   * @return string[]
   *   Singles.
   */
  public function singularize(string $original): array {
    $singular = $this->inflector->singularize($original);
    $this->moduleHandler->invokeAll('graphql_compose_singularize_alter', [
      $original,
      &$singular,
    ]);
    return $singular;
  }

  /**
   * Returns the plural forms of a string.
   *
   * If the method can't determine the form with certainty,
   * several possible plurals are returned.
   *
   * @return string[]
   *   Plurals.
   */
  public function pluralize(string $original): array {
    $plural = $this->inflector->pluralize($original);
    $this->moduleHandler->invokeAll('graphql_compose_pluralize_alter', [
      $original,
      &$plural,
    ]);
    return $plural;
  }

}
