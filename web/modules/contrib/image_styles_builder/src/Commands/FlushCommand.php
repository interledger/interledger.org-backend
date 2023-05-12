<?php

namespace Drupal\image_styles_builder\Commands;

use Drupal\image_styles_builder\DerivativeManager;
use Drupal\image_styles_builder\ImageStyleFlusher;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\Table;

/**
 * Image Styles Flush Command.
 */
class FlushCommand extends DrushCommands {

  /**
   * The derivative manager.
   *
   * @var \Drupal\image_styles_builder\DerivativeManager
   */
  protected $derivativeManager;

  /**
   * The Image Style flusher.
   *
   * @var \Drupal\image_styles_builder\ImageStyleFlusher
   */
  protected $imageStyleFlusher;

  /**
   * FlushCommand constructor.
   *
   * @param \Drupal\image_styles_builder\DerivativeManager $derivative_manager
   *   The derivative plugin manager.
   * @param \Drupal\image_styles_builder\ImageStyleFlusher $image_style_flusher
   *   The Image Style flusher.
   */
  public function __construct(DerivativeManager $derivative_manager, ImageStyleFlusher $image_style_flusher) {
    $this->derivativeManager = $derivative_manager;
    $this->imageStyleFlusher = $image_style_flusher;
  }

  /**
   * Flush image styles based on discovered Derivative(s) Plugins.
   *
   * @command image_styles_builder:flush
   *
   * @aliases isb:flush
   *
   * @usage drush isb:flush
   *   Lookup for all Derivative(s) Plugins and flush declared image-styles.
   */
  public function flush(): void {
    $this->output()->writeln('Discovering derivatives definitions.');
    $derivatives = $this->derivativeManager->getDefinitions();

    $this->output()->writeln(sprintf('%d derivatives founded.', \count($derivatives)));

    $choices = ['all' => 'All'];

    foreach ($derivatives as $id => $derivative) {
      $choices[$id] = sprintf('%s (%s) | %d styles ', $derivative['label'], $derivative['suffix'], \count($derivative['styles']));
    }
    $answer = $this->io()->choice('Select derivative(s) to be flushed', $choices, 'All');

    if ($answer !== 'all') {
      $derivatives = [$derivatives[$answer]];
    }

    $table = new Table($this->output());
    $table
      ->setHeaders(['Derivative', 'Label']);

    foreach ($derivatives as $derivative) {
      $styles = $derivative['styles'];

      foreach ($styles as $id => $style) {
        $style = new ImageStyle($id, [], $derivative['suffix']);
        $this->imageStyleFlusher->flush($style);

        $table->addRow([
          $derivative['id'],
          $style->getId(),
        ]);
      }
    }

    $this->output()->writeln('Successfully flushed image styles.');
    $table->render();
  }

}
