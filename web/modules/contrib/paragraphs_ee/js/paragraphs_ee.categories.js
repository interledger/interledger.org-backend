(function ($, Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Filter items in dialog by a given search string.
   *
   * @param object $dialog
   *   The dialog to filter items.
   * @param string search
   *   The string to search for.
   * @param bool search_description
   *   If <code>true</code> the items description will be searched also.
   */
  var filterItems = function ($dialog, search, search_description) {
    if ('' === search) {
      // Display all potentially hidden elements.
      $('.button-group', $dialog).removeClass('js-hide');
      $('.paragraphs-add-dialog-row', $dialog).removeClass('js-hide');
      return;
    }
    // Hide rows matching the input.
    $('.paragraphs-add-dialog-row', $dialog).each(function () {
      var $row = $(this);
      var input_found = $('.paragraphs-label', $row).html().toLowerCase().indexOf(search.toLowerCase()) !== -1;
      var description = $('.paragraphs-description', $row).html() || '';
      if (search_description) {
        input_found |= (description.toLowerCase().indexOf(search.toLowerCase()) !== -1);
      }
      if (input_found) {
        $row.removeClass('js-hide');
      }
      else {
        $row.addClass('js-hide');
      }
    });
    // Hide categories if no rows are visible.
    $('.button-group', $dialog).each(function () {
      var $group = $(this);
      if ($('.paragraphs-add-dialog-row.js-hide', $group).length === $('.paragraphs-add-dialog-row', $group).length) {
        $group.addClass('js-hide');
      }
      else {
        $group.removeClass('js-hide');
      }
    });
  };

  /**
   * Init display toggle for listing in paragraphs modal.
   */
  Drupal.behaviors.initParagraphsEEDialogDisplayToggle = {
    attach: function (context) {
      $('.paragraphs-add-dialog--categorized', context).each(function () {
        var $dialog_wrapper = $(this).closest('.ui-dialog-content, .paragraphs-add-wrapper');
        var $dialog_header = $dialog_wrapper.find('.dialog-header');

        $dialog_header.each(function (delta, elem) {
          $(once('paragraphs-ee-dialog-toogle', '.display-toggle', elem)).on('click', function () {
            var $self = $(this);
            var $dialog_wrapper = $(this).closest('.ui-dialog-content');
            var $dialog = $dialog_wrapper.find('.paragraphs-add-dialog--categorized');

            if ($self.hasClass('style-list')) {
              $dialog_wrapper.addClass('paragraphs-style-list');
              $dialog_wrapper.parent().addClass('paragraphs-style-list');
            }
            else {
              $dialog_wrapper.removeClass('paragraphs-style-list');
              $dialog_wrapper.parent().removeClass('paragraphs-style-list');
            }
          });
        });
      });
    }
  };

  /**
   * Init filter for paragraphs in paragraphs modal.
   */
  Drupal.behaviors.initParagraphsEEDialogFilter = {
    attach: function (context) {
      $('.paragraphs-add-dialog--categorized', context).each(function () {
        var $dialog = $(this);
        var $dialog_header = $dialog.parent().find('.dialog-header');
        if ($('.paragraphs-add-dialog-row', $dialog).length < 3) {
          // We do not need to enable the filter for very few items.
          return;
        }

        var $filter_wrapper = $('.filter', $dialog_header);
        $filter_wrapper.removeClass('js-hide');

        $filter_wrapper.each(function (delta, elem) {
          $(once('paragraphs-ee-dialog-item-filter', '.item-filter', elem)).on('input', function () {
            var $self = $(this);
            var $dialog_wrapper = $self.closest('.ui-dialog-content');
            var $dialog = $dialog_wrapper.find('.paragraphs-add-dialog--categorized');
            var $dialog_header = $dialog_wrapper.find('.dialog-header');
            var $search_description = $('.search-description :checkbox', $dialog_header);
            filterItems($dialog, $self.val(), $search_description.is(':checked'));
          });

          $(once('paragraphs-ee-dialog-search-description', '.search-description input[type=checkbox]', elem)).on('change', function () {
            var $self = $(this);
            var $dialog_wrapper = $self.closest('.ui-dialog-content');
            var $dialog = $dialog_wrapper.find('.paragraphs-add-dialog--categorized');
            var $dialog_header = $dialog_wrapper.find('.dialog-header');
            var $filter = $('.item-filter', $dialog_header);
            filterItems($dialog, $filter.val(), $self.is(':checked'));
          });
        });
      });
    }
  };

}(jQuery, Drupal, drupalSettings, once));
