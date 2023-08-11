<?php

declare(strict_types=1);

namespace Drupal\graphql_compose;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * Language inflector service.
 */
class LanguageInflector {

  use StringTranslationTrait;

  /**
   * Construct language inflector.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Symfony\Component\String\Inflector\InflectorInterface $inflector
   *   Inflector service.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected InflectorInterface $inflector
  ) {}

  /**
   * Returns the plural forms of a string.
   *
   * If the method can't determine the form with certainty,
   * several possible plurals are returned.
   *
   * @return string[]
   *   Plural form(s) of a string.
   *
   * @see hook_graphql_compose_pluralize_alter()
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
