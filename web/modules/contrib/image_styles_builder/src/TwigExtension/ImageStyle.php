<?php

namespace Drupal\image_styles_builder\TwigExtension;

use Drupal\image_styles_builder\DerivativeManager;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle as ImageStylePlugin;

/**
 * Provide function to fetch all image styles.
 */
class ImageStyle extends AbstractExtension {
  use ContainerAwareTrait;

  /**
   * List of all Twig functions.
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('isb_image_styles', [
        $this, 'getImageStyles',
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'image_styles_builder.twig_extension.image_style';
  }

  /**
   * Get all image styles defined on a derivative Plugin.
   *
   * @param string $derivative_id
   *   The Derivative Plugin ID.
   *
   * @return \Drupal\image_styles_builder\Plugin\Derivative\ImageStyle[]
   *   The complete collection of image styles.
   */
  public function getImageStyles(string $derivative_id): array {
    $derivative_manager = $this->getDerivativeManager();
    $definition = $derivative_manager->getDefinition($derivative_id);

    if (!isset($definition['styles'])) {
      return [];
    }

    $styles = [];
    foreach ($definition['styles'] as $id => $style) {
      $style = new ImageStylePlugin($id, $style['effects'], $definition['suffix']);
      $styles[] = $style->getId();
    }

    return $styles;
  }

  /**
   * The derivative manager.
   *
   * @return \Drupal\image_styles_builder\DerivativeManager
   *   Return the derivative manager.
   */
  protected function getDerivativeManager(): DerivativeManager {
    return $this->container->get('plugin.manager.image_styles_builder.derivative');
  }

}
