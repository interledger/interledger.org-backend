<?php

namespace Drupal\Tests\image_styles_builder\Kernel\TwigExtension;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Image style Twig extension.
 *
 * @coversDefaultClass \Drupal\image_styles_builder\TwigExtension\ImageStyle
 *
 * @group image_styles_builder
 * @group image_styles_builder_kernel
 *
 * @internal
 */
final class ImageStyleTest extends KernelTestBase {

  /**
   * The Image style Twig extension.
   *
   * @var \Drupal\image_styles_builder\TwigExtension\ImageStyle
   */
  protected $imageStyleTwigExt;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'image_styles_builder',
    'image_styles_builder_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->imageStyleTwigExt = $this->container->get('image_styles_builder.twig_extension.image_style');
  }

  /**
   * @covers ::getImageStyles
   */
  public function testGetImageStyles(): void {
    $image_styles = $this->imageStyleTwigExt->getImageStyles('default');
    self::assertCount(14, $image_styles);
  }

  /**
   * @covers ::getImageStyles
   */
  public function testGetImageStylesExceptionOnUnexistingPluginDerivativeId(): void {
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "foo" plugin does not exist. Valid plugin IDs for Drupal\image_styles_builder\DerivativeManager are: default');
    $this->imageStyleTwigExt->getImageStyles('foo');
  }

}
