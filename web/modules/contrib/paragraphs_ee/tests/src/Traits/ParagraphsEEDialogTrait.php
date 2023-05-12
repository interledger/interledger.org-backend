<?php

namespace Drupal\Tests\paragraphs_ee\Traits;

use Behat\Mink\Element\NodeElement;

/**
 * Provides helper methods for the enhanced "Add paragraph" dialog.
 *
 * @group paragraphs_ee
 */
trait ParagraphsEEDialogTrait {

  /**
   * Open the enhanced dialog to insert a paragraph at a specific position.
   *
   * Example:
   *   A paragraph added using the dialog opened by
   *   <code>openDialog('field_paragraphs', 0)</code> will add the paragraph as
   *   first paragraph in the selected field.
   * Example:
   *   A paragraph added using the dialog opened by
   *   <code>openDialog('field_paragraphs')</code> will add the paragraph as
   *   last paragraph in the selected field.
   *
   * @param string $field_name
   *   Name of the paragraphs field.
   * @param int $delta
   *   Where to add paragraphs to the field using the opened dialog.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The NodeElement for the opened dialog or NULL in case the dialog cannot
   *   be opened.
   */
  protected function openDialog($field_name, $delta = -1): ?NodeElement {
    $session = $this->getSession();
    $page = $session->getPage();

    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assertSession */
    $assertSession = $this->assertSession();

    $css_selector_field = 'edit-' . strtr($field_name, ['_' => '-']);

    /** @var \Behat\Mink\Element\NodeElement[] $add_in_between_rows */
    $add_in_between_rows = $page->findAll('css', '[data-drupal-selector=' . $css_selector_field . '] .paragraphs-features__add-in-between__row');
    $this->assertNotEmpty($add_in_between_rows);

    if ($delta < 0) {
      $delta = count($add_in_between_rows) - 1;
    }
    // Check that specified row exists.
    $this->assertArrayHasKey($delta, $add_in_between_rows, 'Row with specified delta does not exists: [' . $delta . ']');
    /** @var \Behat\Mink\Element\NodeElement $add_in_between_row */
    $add_in_between_row = $add_in_between_rows[$delta];
    /** @var \Behat\Mink\Element\NodeElement $paragraphs_button_add_dialog */
    $paragraphs_button_add_dialog = $add_in_between_row->find('css', 'button.paragraphs_ee__add-in-between__dialog-button');
    $this->assertNotEmpty($paragraphs_button_add_dialog);
    // Open dialog.
    $paragraphs_button_add_dialog->press();

    $dialog = $assertSession->waitForElementVisible('css', '.ui-dialog .paragraphs-ee-dialog-wrapper');
    $this->assertNotEmpty($dialog);

    return $dialog;
  }

  /**
   * Add a paragraph to a field at a specific position.
   *
   * Example:
   *   A paragraph added using the dialog opened by
   *   <code>addParagraph('field_paragraphs', 'text_simple', 0)</code> will add
   *   a paragraph of type "text_simple" as first paragraph in the selected
   *   field.
   * Example:
   *   A paragraph added using the dialog opened by
   *   <code>addParagraph('field_paragraphs', 'text_simple')</code> will add a
   *   paragraph of type "text_simple" as last paragraph in the selected
   *   field.
   *
   * @param string $field_name
   *   Name of the paragraphs field.
   * @param string $paragraph_type
   *   Type of paragraph to add.
   * @param int $delta
   *   Where to add paragraphs to the field using the opened dialog.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The NodeElement of the inserted paragraph or NULL in case of errors.
   */
  protected function addParagraph($field_name, $paragraph_type, $delta = -1): ?NodeElement {
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assertSession */
    $assertSession = $this->assertSession();

    $dialog = $this->openDialog($field_name, $delta);
    $this->assertNotEmpty($dialog);

    // Compose button selector.
    $button_selector = $field_name . '_' . $paragraph_type . '_add_more';

    /** @var \Behat\Mink\Element\NodeElement $button */
    $button = $dialog->find('css', '[name=' . $button_selector . ']');
    $this->assertNotEmpty($button);

    $button->click();

    $css_selector_field = 'edit-' . strtr($field_name, ['_' => '-']);
    $css_selector_paragraph = '.paragraph-type--' . strtr($paragraph_type, ['_' => '-']);

    return $assertSession->waitForElementVisible('css', '[data-drupal-selector=' . $css_selector_field . '] ' . $css_selector_paragraph);
  }

}
