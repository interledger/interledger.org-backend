<?php

namespace Drupal\Tests\paragraphs_ee\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\paragraphs_ee\Traits\ParagraphsEEDialogTrait;

/**
 * Base class for Javascript tests for paragraphs_ee module.
 *
 * @package Drupal\Tests\paragraphs_ee\FunctionalJavascript
 */
abstract class ParagraphsEEJavascriptTestBase extends WebDriverTestBase {

  use LoginAdminTrait;
  use ParagraphsCoreVersionUiTestTrait;
  use ParagraphsEEDialogTrait;
  use ParagraphsTestBaseTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'field_ui',
    'node',
    'paragraphs',
    'paragraphs_features',
    'paragraphs_ee',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if ($theme = getenv('THEME')) {
      $this->assertTrue(\Drupal::service('theme_installer')->install([$theme]));
      $this->container->get('config.factory')
        ->getEditable('system.theme')
        ->set('default', $theme)
        ->set('admin', $theme)
        ->save();
    }
  }

  /**
   * Create content type with paragraph field and additional paragraph types.
   *
   * Paragraph types are prefixed with "test_" and for text types index will be
   * used. (fe. "$num_of_test_paragraphs = 3" will provide following test
   * paragraphs: test_1, test_2, test_3.
   *
   * @param string $content_type
   *   ID for new testing content type.
   * @param int $num_of_test_paragraphs
   *   Number of additional test paragraph types.
   */
  protected function createTestConfiguration($content_type, $num_of_test_paragraphs = 1) {
    $this->addParagraphedContentType($content_type);
    $this->loginAsAdmin([
      'administer content types',
      'administer node form display',
      "edit any $content_type content",
      "create $content_type content",
    ]);

    // Add paragraph types.
    for ($paragraph_type_index = 1; $paragraph_type_index <= $num_of_test_paragraphs; $paragraph_type_index++) {
      $this->addParagraphsType("test_$paragraph_type_index");
      $this->addFieldtoParagraphType("test_$paragraph_type_index", "text_$paragraph_type_index", 'text_long');
    }

    // Set the settings for the field in the paragraphed content type.
    $component = [
      'type' => 'paragraphs',
      'region' => 'content',
      'settings' => [
        'edit_mode' => 'closed',
        'add_mode' => 'modal',
        'form_display_mode' => 'default',
      ],
      'third_party_settings' => [
        'paragraphs_features' => [
          'add_in_between' => TRUE,
          'add_in_between_link_count' => 2,
        ],
      ],
    ];
    EntityFormDisplay::load("node.$content_type.default")
      ->setComponent('field_paragraphs', $component)
      ->save();
  }

  /**
   * Scroll element in middle of browser view.
   *
   * @param string $selector
   *   Selector engine name.
   * @param string|array $locator
   *   Selector locator.
   */
  public function scrollElementInView($selector, $locator) {
    if ($selector === 'xpath') {
      $this->getSession()
        ->executeScript('
          var element = document.evaluate(\'' . addcslashes($locator, '\'') . '\', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
          element.scrollIntoView({block: "center"});
          element.focus({preventScroll:true});
        ');
    }
    else {
      $this->getSession()
        ->executeScript('
          var element = document.querySelector(\'' . addcslashes($locator, '\'') . '\');
          element.scrollIntoView({block: "center"});
        ');
    }
  }

  /**
   * Scroll element in middle of browser view and click it.
   *
   * @param string $selector
   *   Selector engine name.
   * @param string|array $locator
   *   Selector locator.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  public function scrollClick($selector, $locator) {
    $this->scrollElementInView($selector, $locator);
    if ($selector === 'xpath') {
      $this->getSession()->getDriver()->click($locator);
    }
    else {
      $this->click($locator);
    }
  }

}
