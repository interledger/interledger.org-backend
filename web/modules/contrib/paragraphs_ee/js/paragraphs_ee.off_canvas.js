(function ($, Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Defines a behavior to initialize the button behaviors.
   */
  Drupal.behaviors.paragraphsEeOffCanvasEvents = {
    attach: function (context) {
      $(once('paragraphs-ee-off-canvas', 'html')).on({
        'dialog:aftercreate': function dialogAftercreate(event, dialog, $element, settings) {
          if (Drupal.offCanvas.isOffCanvas($element)) {
            var $offCanvasDialog = $('.ui-dialog-off-canvas');
            $offCanvasDialog
                    .addClass('paragraphs-ee-off-canvas')
                    .addClass('paragraphs-ee-off-canvas--browser');

            var $dialogTarget = $('.paragraphs-add-dialog--categorized', $offCanvasDialog);
            var field_name = $('.paragraphs-add-dialog--categorized', $offCanvasDialog).data('field-name');
            var wrapper_selector = 'edit-' + field_name.replace(/_/g, '-') + '-wrapper';
            var $dialogOriginal = $('[data-drupal-selector="' + wrapper_selector + '"] [data-dialog-field-name="' + field_name + '"].active-dialog');
            if (!$dialogOriginal) {
              return;
            }

            if ($dialogOriginal.hasClass('paragraphs-style-list')) {
              $dialogTarget.addClass('paragraphs-style-list');
            }

            // Clone button groups and buttons.
            $('.button-group', $dialogOriginal).each(function () {
              var $groupOriginal = $(this);
              var $group = $('<div>')
                      .addClass('button-group')
                      .addClass('clearfix')
                      .attr('role', 'group')
                      .appendTo($dialogTarget);
              $('.category-title', $groupOriginal)
                      .clone()
                      .appendTo($group);
              $('.summary', $groupOriginal)
                      .clone()
                      .appendTo($group);
              $('.paragraphs-add-dialog-list', $groupOriginal)
                      .clone()
                      .appendTo($group);
            });

            $(once('paragraphs-ee-add-more-each', '.paragraphs-add-dialog-list .paragraphs-button--add-more', $dialogTarget)).each(function () {
              var name_original = $(this).attr('name');
              $(this).attr('data-triggers', name_original);
              $(this).removeAttr('id');
              $(this).removeAttr('name');
              $(this).addClass('paragraphs-add-more-trigger');
            });

            Drupal.attachBehaviors();
            // file.js adds a mousedown-handler to our buttons we do not want.
            $('.js-form-submit', $element).off('mousedown');
          }
        }
      });
    }
  };

  Drupal.behaviors.paragraphsEeOffCanvasButtons = {
    attach: function (context) {
      if (!$('.ui-dialog-off-canvas')) {
        return;
      }
      var $dialog = $('.ui-dialog-off-canvas');
      $('.paragraphs-add-more-trigger', $dialog).each(function () {
        var $trigger = $(this);
        $trigger.off('click.dialog');
        $trigger.on('click', function (event) {
          var $button = $('[name="' + $trigger.data('triggers') + '"]');
          if (!$button) {
            return;
          }
          // Trigger mousedown event of real button.
          $button.trigger('mousedown');

          // Stop default execution of click event.
          event.preventDefault();
          event.stopPropagation();
        });
      });
    }
  };

}(jQuery, Drupal, drupalSettings, once));
