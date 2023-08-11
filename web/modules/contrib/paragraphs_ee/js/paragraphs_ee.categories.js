(function ($, Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Filter items in dialog by a given search string.
   *
   * @param object $dialog
   *   The dialog to filter items.
   * @param string search
   *   The string to search for.
   */
  var filterItems = function ($dialog, search) {
    if ('' === search) {
      // Display all potentially hidden elements.
      $('.button-group', $dialog).removeClass('js-hide');
      $('.paragraphs-button--add-more', $dialog).removeClass('js-hide');
      return;
    }
    // Hide buttons not matching the input.
    $('.paragraphs-button--add-more', $dialog).each(function () {
      var $button = $(this);
      // Search in button label.
      var input_found = $('.paragraphs-label', $button).html().toLowerCase().indexOf(search.toLowerCase()) !== -1;
      var description = $('.paragraphs-description', $button).html() || '';
      // Search in button description.
      input_found |= (description.toLowerCase().indexOf(search.toLowerCase()) !== -1);
      if (input_found) {
        $button.removeClass('js-hide');
      }
      else {
        $button.addClass('js-hide');
      }
    });
    // Hide categories if no buttons are visible.
    $('.button-group', $dialog).each(function () {
      var $group = $(this);
      if ($('.paragraphs-button--add-more.js-hide', $group).length === $('.paragraphs-button--add-more', $group).length) {
        $group.addClass('js-hide');
      }
      else {
        $group.removeClass('js-hide');
      }
    });
  };

  /**
   * Init filter for paragraphs in paragraphs modal.
   */
  Drupal.behaviors.initParagraphsEEDialogFilter = {
    attach: function (context) {
      $('.paragraphs-add-dialog--categorized', context).each(function () {
        var $dialog = $(this);
        if ($('.paragraphs-button--add-more', $dialog).length < 3) {
          // We do not need to enable the filter for very few items.
          return;
        }

        var $filter_wrapper = $('.filter', $dialog);
        $filter_wrapper.removeClass('js-hide');

        $filter_wrapper.each(function (delta, elem) {
          $(once('paragraphs-ee-dialog-item-filter', '.item-filter', elem)).on('input', function () {
            var $self = $(this);
            var $dialog_wrapper = $self.closest('.ui-dialog-content');
            filterItems($dialog_wrapper, $self.val());
          });
        });
      });
    }
  };

  /**
   * Init filter for categories in paragraphs modal.
   */
  Drupal.behaviors.initParagraphsEEDialogCategoriesFilter = {
    attach: function (context) {
      $('.paragraphs-add-dialog--categorized', context).each(function () {
        // Add handler for "All categories".
        var $tabCategoriesAll = $('.paragraphs-ee-category-list-item__all', $(this));
        $tabCategoriesAll.on('click', function () {
          var $dialog = $(this).closest('.paragraphs-ee-add-dialog');
          // Display all button groups.
          $('.paragraphs-ee-buttons .button-group', $dialog).removeClass('is-hidden');

          // Remove highlighting from previously selected category.
          $('.paragraphs-ee-category-list-item', $dialog).removeClass('is-selected');
          // Mark current item as selected.
          $(this).addClass('is-selected');
        });

        // Add handler for paragraph categories.
        var $tabCategories = $('.paragraphs-ee-category-list-item:not(.paragraphs-ee-category-list-item__all)', $(this));
        $tabCategories.on('click', function () {
          var $dialog = $(this).closest('.paragraphs-ee-add-dialog');
          // Hide all button groups.
          $('.paragraphs-ee-buttons .button-group', $dialog).addClass('is-hidden');
          // Display selected button group.
          var button_group_id = $('a', $(this)).attr('href');
          $('.paragraphs-ee-buttons ' + button_group_id, $dialog).removeClass('is-hidden');

          // Remove highlighting from previously selected category.
          $('.paragraphs-ee-category-list-item', $dialog).removeClass('is-selected');
          // Mark current item as selected.
          $(this).addClass('is-selected');

          return false;
        });

      });
    }
  };

}(jQuery, Drupal, drupalSettings, once));
