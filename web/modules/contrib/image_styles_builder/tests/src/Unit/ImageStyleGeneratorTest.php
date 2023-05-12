<?php

namespace Drupal\Tests\image_styles_builder\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\image\ImageEffectManager;
use Drupal\image_styles_builder\ImageStyleGenerator;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests image style generator.
 *
 * @coversDefaultClass \Drupal\image_styles_builder\ImageStyleGenerator
 * @group image_styles_builder
 *
 * @internal
 */
final class ImageStyleGeneratorTest extends UnitTestCase {

  /**
   * An instance of the ImageStyleGenerator.
   *
   * @var \Drupal\image_styles_builder\ImageStyleGenerator
   */
  protected $imageStyleGenerator;

  /**
   * A mocked instance of an image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $imageStyleStorage;

  /**
   * A mocked instance of a logger.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the dependencies to inject into the image style generator.
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->imageStyleStorage = $this->prophesize(EntityStorageInterface::class);
    $image_effect_manager = $this->prophesize(ImageEffectManager::class);
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);

    $entity_type_manager->getStorage('image_style')
      ->willReturn($this->imageStyleStorage);

    $logger_factory->get('image_styles_builder')
      ->willReturn($this->logger)
      ->shouldBeCalledOnce();

    // Instantiate the image style generator.
    $this->imageStyleGenerator = new ImageStyleGenerator(
      $entity_type_manager->reveal(),
      $image_effect_manager->reveal(),
      $logger_factory->reveal(),
    );
  }

  /**
   * Tests the logger will create a notice when image styles already exists.
   *
   * @covers ::generate
   */
  public function testLoggerImageStyleAlreadyExists() {
    $this->imageStyleStorage->load('foo')
      ->willReturn(TRUE)
      ->shouldBeCalledOnce();

    $this->logger->notice('The image style @machine_name already exists.', ['@machine_name' => 'foo'])
      ->shouldBeCalledOnce();

    $image_style = new ImageStyle('foo', []);
    self::assertNull($this->imageStyleGenerator->generate($image_style));
  }

}
