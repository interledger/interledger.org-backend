<?php

namespace Drupal\Tests\paragraphs_ee\FunctionalJavascript;

/**
 * Tests the enhanced add-in-between paragraphs feature.
 *
 * @group paragraphs_ee
 */
class ParagraphsEEAddInBetweenTest extends ParagraphsEEJavascriptTestBase {

  /**
   * Tests the add widget button with modal form.
   */
  public function testAddInBetweenFeature() {
    // Create paragraph types and content types with required configuration for
    // testing of add in between feature.
    $content_type = 'test_modal_delta';

    // Create four text test paragraphs.
    $this->createTestConfiguration($content_type, 4);

    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assertSession */
    $assertSession = $this->assertSession();

    // Check that add in between functionality is used.
    $this->drupalGet("node/add/$content_type");

    $this->assertEquals(FALSE, $driver->isVisible('//*[@name="button_add_modal"]'), 'Default "Add Paragraph" button should be hidden.');
    $this->assertEquals(TRUE, $driver->isVisible('//button[contains(concat(" ", normalize-space(@class), " "), " paragraphs-features__add-in-between__button ")]'), 'New add in between button should be visible.');

    $base_buttons = $page->findAll('css', '.paragraphs-features__add-in-between__button[data-easy-access-weight]');
    $this->assertEquals(2, count($base_buttons), "There should be 2 add in between buttons.");

    // Open dialog and add a paragraph.
    $dialog = $this->openDialog('field_paragraphs');

    $button = $dialog->find('css', '[data-drupal-selector=field-paragraphs-test-1-add-more]');
    $this->assertNotEmpty($button);
    $button->press();
    $assertSession->waitForElementVisible('css', '[data-drupal-selector=field-paragraphs] .paragraph-type--test-1');

    // Test adding a paragraph directly.
    /** @var \Behat\Mink\Element\NodeElement $paragraph */
    $paragraph = $this->addParagraph('field_paragraphs', 'test_2');
    $this->assertNotEmpty($paragraph);
  }

}
