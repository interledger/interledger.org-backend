<?php

declare(strict_types = 1);

namespace Drupal\cdn\Asset;

use Drupal\Core\Asset\AssetOptimizerInterface;

/**
 * Decorates CSS asset optimizer: ensures file URLs are rewritten to the CDN.
 *
 * @see cdn_file_url_alter()
 * @see https://www.drupal.org/node/2745109
 */
class CssOptimizer implements AssetOptimizerInterface {

  /**
   * @param \Drupal\Core\Asset\AssetOptimizerInterface $decoratedCssOptimizer
   *   The decorated CSS asset optimizer service.
   */
  public function __construct(protected AssetOptimizerInterface $decoratedCssOptimizer) {}

  /**
   * {@inheritdoc}
   */
  public function optimize(array $css_asset) {
    return $this->runWithoutCdnFileAlteration(function () use ($css_asset) {
      return $this->decoratedCssOptimizer->optimize($css_asset);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function clean($contents) {
    return $this->runWithoutCdnFileAlteration(function () use ($contents) {
      return $this->decoratedCssOptimizer->clean($contents);
    });
  }

  /**
   * Wraps callable in an environment where the global $cdn_in_css_file===FALSE.
   *
   * @param callable $callable
   *   A callable.
   *
   * @return mixed
   *   The result of the callable.
   */
  protected function runWithoutCdnFileAlteration(callable $callable) {
    global $_cdn_in_css_file;
    $_cdn_in_css_file = TRUE;
    $result = $callable();
    $_cdn_in_css_file = FALSE;
    return $result;
  }

}
