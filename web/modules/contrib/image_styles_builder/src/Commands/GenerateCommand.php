<?php

namespace Drupal\image_styles_builder\Commands;

use Drupal\image_styles_builder\DerivativeManager;
use Drupal\image_styles_builder\ImageStyleGenerator;
use Drupal\image_styles_builder\Plugin\Derivative\ImageEffect;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\Table;

/**
 * Image Styles Generate Command.
 */
class GenerateCommand extends DrushCommands {

  /**
   * The derivative manager.
   *
   * @var \Drupal\image_styles_builder\DerivativeManager
   */
  protected $derivativeManager;

  /**
   * The Image Style generator.
   *
   * @var \Drupal\image_styles_builder\ImageStyleGenerator
   */
  protected $imageStyleGenerator;

  /**
   * GenerateCommand constructor.
   *
   * @param \Drupal\image_styles_builder\DerivativeManager $derivative_manager
   *   The derivative plugin manager.
   * @param \Drupal\image_styles_builder\ImageStyleGenerator $image_style_generator
   *   The Image Style generator.
   */
  public function __construct(DerivativeManager $derivative_manager, ImageStyleGenerator $image_style_generator) {
    $this->derivativeManager = $derivative_manager;
    $this->imageStyleGenerator = $image_style_generator;
  }

  /**
   * Generate image styles based on discovered Derivative(s) Plugins.
   *
   * @command image_styles_builder:generate
   *
   * @aliases isb:gen
   *
   * @usage drush isb:gen
   *   Lookup for all Derivative(s) Plugins and generate declared image-styles.
   */
  public function generate(): void {
    $this->output()->writeln('Discovering derivatives definitions.');
    $derivatives = $this->derivativeManager->getDefinitions();

    $this->output()->writeln(sprintf('%d derivatives founded.', \count($derivatives)));

    $choices = ['all' => 'All'];

    foreach ($derivatives as $id => $derivative) {
      $choices[$id] = sprintf('%s (%s) | %d styles ', $derivative['label'], $derivative['suffix'], \count($derivative['styles']));
    }
    $answer = $this->io()->choice('Select derivative(s) to be generated', $choices, 'All');

    if ($answer !== 'all') {
      $derivatives = [$derivatives[$answer]];
    }

    $table = new Table($this->output());
    $table
      ->setHeaders(['Derivative', 'Label', 'Effects']);

    foreach ($derivatives as $derivative) {
      $styles = $derivative['styles'];

      foreach ($styles as $id => $style) {
        $style = new ImageStyle($id, $style['effects'], $derivative['suffix']);
        $image_style = $this->imageStyleGenerator->generate($style);

        if (!$image_style) {
          continue;
        }

        $effects_string = array_map(static function (ImageEffect $effect) {
          return $effect->__toString();
        }, $style->getEffects());

        $table->addRow([
          $derivative['id'],
          $image_style->getName(),
          implode(', ', $effects_string),
        ]);
      }
    }

    $this->output()->writeln('Successfully generated image styles.');
    $table->render();
  }

}
