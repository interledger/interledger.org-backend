(function ($, Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Ensure namespace for paragraphs_ee exists.
   */
  Drupal.paragraphs_ee = Drupal.paragraphs_ee || {};

  /**
   * Init paragraphs widget with add in between functionality.
   *
   * @param {HTMLDocument|HTMLElement} [context=document]
   *   An element to attach behaviors to.
   * @param {{wrapperId:string, linkCount:number}} field
   *   The paragraphs field config.
   */
  Drupal.paragraphs_features.add_in_between.initParagraphsWidget = function (context, field) {
    const [table] = once('paragraphs-features-add-in-between-init', context.querySelector('.field-multiple-table'));
    if (!table) {
      return;
    }
    const addModalBlock = Drupal.paragraphs_features.add_in_between.getAddModalBlock(table);
    // Ensure that paragraph list uses modal dialog.
    if (!addModalBlock) {
      return;
    }
    // A new button for adding at the end of the list is used.
    addModalBlock.style.display = 'none';

    const addModalButton = addModalBlock.querySelector('.paragraph-type-add-modal-button');
    const dialog = addModalBlock.querySelector('.paragraphs-add-dialog');
    if (!dialog) {
      return;
    }

    const rowButtonElement = () => {
      const buttons = [];
      const buttonsAllCount = Array.from(dialog.querySelectorAll('.paragraphs-button--add-more:not([data-paragraphs-ee-button-clone])')).length;
      const addButtons = Array.from(dialog.querySelectorAll('input[data-easy-access-weight], button[data-easy-access-weight]'));

      addButtons.slice(0, field.linkCount).forEach((addButton) => {
        // Set title attribute.
        const title = addButton.value;
        const doc = new DOMParser().parseFromString(title, 'text/html');
        const buttonTitle = doc.documentElement.textContent;
        // Create a remote button triggering original add button in dialog.
        const button = Drupal.theme('paragraphsFeaturesAddInBetweenButton', {title: buttonTitle});
        button.innerHTML = Drupal.t('+ @title', {'@title': buttonTitle}, {context: 'Paragraphs Editor Enhancements'});;
        button.setAttribute('title', Drupal.t('Add !title', {'!title': buttonTitle}, {context: 'Paragraphs Editor Enhancements'}));
        button.setAttribute('aria-label', Drupal.t('Add !title', {'!title': buttonTitle}, {context: 'Paragraphs Editor Enhancements'}));
        button.setAttribute('data-paragraph-bundle', addButton.dataset.paragraphBundle);
        button.setAttribute('data-easy-access-weight', 100);
        if ('easyAccessWeight' in addButton.dataset) {
          button.setAttribute('data-easy-access-weight', addButton.dataset.easyAccessWeight);
        }
        button.setAttribute('formnovalidate', 'formnovalidate');

        Drupal.paragraphs_features.addEventListenerToButton(button, addButton);
        buttons.push(button);
      });

      // Sort list based on the buttons weight.
      buttons.sort(function (a, b) {
        return (parseInt(a.dataset.easyAccessWeight) + 1000) - (parseInt(b.dataset.easyAccessWeight) + 1000);
      });

      // Add more (...) button triggering dialog open.
      if (buttonsAllCount > field.linkCount) {
        const title = field.linkCount ?
          Drupal.t('+', {}, {context: 'Paragraphs Features'}) :
          Drupal.t('+ Add', {}, {context: 'Paragraphs Features'});
        const button = Drupal.theme('paragraphsFeaturesAddInBetweenMoreButton', {title: title, settings: dialog.dataset});

        Drupal.paragraphs_ee.addEventListenerToMoreButton(button);
        buttons.push(button);
      }

      if (buttons.length > 0) {
        // First item needs a special class.
        buttons[0].classList.add('first');
        // The last button in the list needs a special class.
        buttons[buttons.length - 1].classList.add('last');
      }

      return Drupal.theme('paragraphsFeaturesAddInBetweenRow', buttons);
    };

    let tableBody = table.querySelector(':scope > tbody');

    // Add a new button for adding a new paragraph to the end of the list.
    if (!tableBody) {
      tableBody = document.createElement('tbody');
      table.append(tableBody);
    }

    tableBody.querySelectorAll(':scope > tr').forEach((rowElement) => {
      rowElement.insertAdjacentElement('beforebegin', rowButtonElement());

      const rowSelector = '.paragraphs-features__add-in-between__row';
      var $self = $(rowElement);
      $self.on('mouseover', function () {
        $self.prev(rowSelector).find('.paragraphs-features__add-in-between__wrapper').addClass('is-active');
        $self.next(rowSelector).find('.paragraphs-features__add-in-between__wrapper').addClass('is-active');
      });
      $self.on('mouseout', function () {
        $self.prev(rowSelector).find('.paragraphs-features__add-in-between__wrapper').removeClass('is-active');
        $self.next(rowSelector).find('.paragraphs-features__add-in-between__wrapper').removeClass('is-active');
      });
    });
    tableBody.appendChild(rowButtonElement());

    // Adding of a new paragraph can be disabled for some reason.
    if (addModalButton.getAttribute('disabled')) {
      tableBody.querySelectorAll('.paragraphs-features__add-in-between__button').forEach((button) => {
        button.setAttribute('disabled', 'disabled');
        button.classList.add('is-disabled');
      });
    }

    if (('dialogOffCanvas' in dialog.dataset) && (dialog.dataset.dialogOffCanvas === 'true')) {
      Drupal.ajax.bindAjaxLinksWithProgress(tableBody.querySelectorAll('.paragraphs-features__add-in-between__wrapper'));
    }
  };

  /**
   * Get paragraphs add modal block in various themes structures.
   *
   *  gin:
   *   .layer-wrapper .gin-table-scroll-wrapper table
   *   .form-actions
   * claro:
   *   table
   *   .form-actions
   * thunder-admin / seven:
   *   table
   *   .clearfix
   *
   * @param {HTMLElement} table
   * The table element.
   *
   * @return {HTMLElement} addModalBlock
   *   the add modal block element.
   */
  Drupal.paragraphs_features.add_in_between.getAddModalBlock = (table) => {
    const fromParent = (elem) => {
      let sibling = elem.parentNode.firstChild;
      while (sibling) {
        if (sibling.nodeType === 1 && sibling !== elem) {
          const addModalBlock = sibling.querySelector('.paragraphs-add-wrapper');
          if (addModalBlock) {
            return addModalBlock;
          }
        }
        sibling = sibling.nextSibling;
      }
    };
    return fromParent(table) || fromParent(table.parentNode) || fromParent(table.parentNode.parentNode);
  };

  /**
   * Add listener for triggering drupal inputs.
   *
   * @param {HTMLElement} button
   *   The button to add the event on.
   * @param {HTMLElement=} addButton
   *   The original button to click.
   */
  Drupal.paragraphs_features.addEventListenerToButton = (button, addButton) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const dialog = Drupal.paragraphs_features.add_in_between.getAddModalBlock(event.target.closest('table')).querySelector('.paragraphs-add-dialog');
      const row = event.target.closest('tr');
      const delta = Array.prototype.indexOf.call(row.parentNode.children, row) / 2;

      // Set delta where new paragraph should be inserted.
      Drupal.paragraphs_features.add_in_between.setDelta(dialog, delta);

      // Trigger event on original button or open modal.
      addButton ?
        addButton.dispatchEvent(new MouseEvent('mousedown')) :
        Drupal.paragraphsAddModal.openDialog(dialog, Drupal.t('Add @widget_title', {'@widget_title': dialog.dataset.widgetTitle}, {context: 'Paragraphs Editor Enhancements'}));
    });
  };

  /**
   * Add listener for triggering the "more paragraphs" button.
   *
   * @param {HTMLElement} button
   *   The button to add the event on.
   */
  Drupal.paragraphs_ee.addEventListenerToMoreButton = (button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const dialog = Drupal.paragraphs_features.add_in_between.getAddModalBlock(event.target.closest('table')).querySelector('.paragraphs-add-dialog');
      const row = event.target.closest('tr');
      const delta = Array.prototype.indexOf.call(row.parentNode.children, row) / 2;

      // Set delta where new paragraph should be inserted.
      Drupal.paragraphs_features.add_in_between.setDelta(dialog, delta);

      if (dialog.hasAttribute('data-dialog-off-canvas') && dialog.dataset.dialogOffCanvas === 'true') {
        document.querySelector('.paragraphs-add-dialog--categorized').classList.remove('active-dialog');
        const active_subform = event.target.closest('.js-form-item');
        if (active_subform) {
          active_subform.querySelector('.paragraphs-add-dialog--categorized').classList.add('active-dialog');
        }
      }
      else {
        // Open simple dialog.
        Drupal.paragraphsAddModal.openDialog(dialog.parentElement, Drupal.t('Add @widget_title', {'@widget_title': dialog.dataset.widgetTitle}, {context: 'Paragraphs Editor Enhancements'}));
      }
    });
  };

  /**
   * Define add in between more button template.
   *
   * @param {object} config
   *   Configuration for add in between button.
   *
   * @return {HTMLElement}
   *   Returns element for add in between button.
   */
  Drupal.theme.paragraphsFeaturesAddInBetweenMoreButton = (config) => {
    const use_off_canvas = (('dialogOffCanvas' in config.settings) && (config.settings.dialogOffCanvas === 'true'));

    // Define default button.
    let button = document.createElement('button');

    if (use_off_canvas) {
      button = document.createElement('a');
      button.classList.add('paragraphs_ee__add-in-between__dialog-button--off-canvas', 'use-ajax');
      button.setAttribute('href', config.settings.dialogBrowserUrl);
      button.setAttribute('data-progress-type', 'fullscreen');
      button.setAttribute('data-dialog-type', 'dialog');
      button.setAttribute('data-dialog-renderer', 'off_canvas');
      button.setAttribute('data-dialog-options', '{"width":"25vw"}');
      button.setAttribute('formnovalidate', 'formnovalidate');
    }

    button.innerText = Drupal.t('@title', {'@title': config.title}, {context: 'Paragraphs Features'});
    button.setAttribute('title', Drupal.t('Show all @title_plural', {'@title_plural': config.settings.widgetTitlePlural}, {context: 'Paragraphs Editor Enhancements'}));
    button.classList.add('paragraphs-features__add-in-between__button', 'paragraphs_ee__add-in-between__dialog-button', 'button--small', 'js-show', 'button', 'js-form-submit', 'form-submit');

    return button;
  };

  /**
   * Clone of Drupal.ajax.bindAjaxLinks allowing to set progress type.
   *
   * @param {HTMLElement} element
   *   Element to enable Ajax functionality for.
   *
   * @todo Remove if https://www.drupal.org/project/drupal/issues/2818463 has
   *   been committed.
   */
  Drupal.ajax.bindAjaxLinksWithProgress = function (element) {
    if (!(element instanceof Element)) {
      return;
    }

    once('paragraphs-ee-ajax', '.use-ajax', element).forEach((ajaxLink) => {
      var $linkElement = $(ajaxLink);

      var elementSettings = {
        /**
         * Allow overriding the progress type using the data-progress-type
         * attribute on the element.
         */
        progress: {
          type: $linkElement.data('progress-type') || 'throbber'
        },
        dialogType: $linkElement.data('dialog-type'),
        dialog: $linkElement.data('dialog-options'),
        dialogRenderer: $linkElement.data('dialog-renderer'),
        base: $linkElement.attr('id'),
        element: ajaxLink
      };
      var href = $linkElement.attr('href');
      /**
       * For anchor tags, these will go to the target of the anchor rather than
       * the usual location.
       */
      if (href) {
        elementSettings.url = href;
        elementSettings.event = 'click';
      }
      Drupal.ajax(elementSettings);
    });
  };

  /**
   * Clone of Drupal.paragraphsAddModal.openDialog allowing to override the
   * width of the popup.
   *
   * @todo Remove if https://www.drupal.org/project/paragraphs/issues/3159884 has
   *   been committed.
   */
  Drupal.paragraphsAddModal.openDialog = function (element, title, options) {
    var $element = $(element);

    // Get the delta element before moving $element to dialog element.
    var $modalDelta = $element.parent().find('.paragraph-type-add-modal-delta, .paragraph-type-add-delta.modal');

    var dialogStyle = drupalSettings.paragraphs_ee.dialog_style || 'tiles';

    // Calculate initial width of dialog.
    var dialogWidth = 'auto';
    var windowWidth = $(window).width();
    var dialogMaxWidth = 1170;
    if (windowWidth > (dialogMaxWidth + 50)) {
      dialogWidth = dialogMaxWidth + 'px';
    }

    // Deep clone with all attached events. We need to work on cloned element
    // and not directly on origin because Drupal dialog.ajax.js
    // Drupal.behaviors.dialog will do remove of origin element on dialog close.
    var default_options = {
      // Turn off autoResize from dialog.position so draggable is not disabled.
      autoResize: true,
      resizable: false,
      dialogClass: 'paragraphs-ee-add-dialog paragraphs-ee-add-dialog--categorized' + (dialogStyle == 'tiles' ? '' : ' paragraphs-style-' + dialogStyle),
      title: title,
      width: dialogWidth,
      maxWidth: dialogMaxWidth,
      paragraphsModalDelta: $modalDelta
    };
    $element = $element.clone(true);
    options = $.extend({}, default_options, options);
    var dialog = Drupal.dialog($element, options);
    dialog.showModal();

    // Close the dialog after a button was clicked.
    // Use mousedown event, because we are using ajax in the modal add mode
    // which explicitly suppresses the click event.
    $(once('paragraphs-ee-dialog-submit', '.field-add-more-submit', $element.get(0))).on('mousedown', function () {
      dialog.close();
    });

    return dialog;
  };

  /**
   * Clone of Drupal.behaviors.paragraphsModalAdd.attach setting the popup
   * width.
   */
  Drupal.behaviors.paragraphsModalAdd.attach = function (context) {
    $(once('paragraphs-ee-add-click-handler', '.paragraph-type-add-modal-button, .paragraph-type-add-delta.modal', context)).on('click', function (event) {
      var $button = $(this);
      Drupal.paragraphsAddModal.openDialog($button.parent().siblings('.paragraphs-ee-dialog-wrapper'), $button.val());

      // Stop default execution of click event.
      event.preventDefault();
      event.stopPropagation();
    });

    $(window).on({
      'dialog:aftercreate': function dialogAfterCreate(event, dialog, $element, settings) {
        if (Drupal.offCanvas && Drupal.offCanvas.isOffCanvas($element)) {
          // Don't run this for off canvas dialogs.
          return;
        }
        if (!once('paragraphs-add-dialog-afterCreate', $element).length) {
          return;
        }

        // Move display toggle buttons to titlebar.
        var $title_bar = $('.ui-dialog-titlebar', $element.parent());
        Drupal.paragraphs_ee.initDialogActionButtons($element.parent(), $title_bar);
      }
    });
  };

  /**
   * Initialize action buttons (e.g. display toggle buttons).
   *
   * @param $dialog
   *   The dialog.
   * @param $element
   *   The element the buttons are attached to.
   */
  Drupal.paragraphs_ee.initDialogActionButtons = function ($dialog, $element) {
    $('.paragraphs-ee-actions-wrapper', $dialog).each(function (delta, elem) {
      $(this).detach().appendTo($element).removeClass('is-hidden');

      var $toggle = $('.display-toggle', elem);
      // Add aria-pressed attributes for screen readers to show which display option is selected.
      var $dialog_content = $dialog.find('.ui-dialog-content');
      if ($dialog_content.hasClass('paragraphs-style-list')) {
        $toggle.filter('.style-list').attr('aria-pressed', 'true');
      }
      else {
        $toggle.filter('.style-tiles').attr('aria-pressed', 'true');
      }

      $(once('paragraphs-ee-dialog-toogle', '.display-toggle', elem)).on('click', function () {
        var $self = $(this);
        var $dialog = $self.closest('.paragraphs-ee-add-dialog');
        var $dialog_wrapper = $dialog.find('[data-paragraphs-ee-dialog-wrapper]');
        var $dialog_buttons = $self.parent().find('.display-toggle');

        // Remove attribute from all buttons.
        $dialog_buttons.removeAttr('aria-pressed');
        // Set accessibility attribute on current button.
        $self.attr('aria-pressed', 'true');

        if ($self.hasClass('style-list')) {
          $dialog_wrapper.addClass('paragraphs-style-list');
          $dialog_wrapper.closest('.paragraphs-ee-add-dialog').addClass('paragraphs-style-list');
        }
        else {
          $dialog_wrapper.removeClass('paragraphs-style-list');
          $dialog_wrapper.parent().removeClass('paragraphs-style-list');
        }
      });
    });

  };

  /**
   * Toggle category list display.
   */
  Drupal.behaviors.paragraphsEEToggleCategoryDisplay = {
    attach: function (context) {
      $(once('paragraphs-ee-category-toggle-handler', '.paragraphs-ee-category-toggle', context)).on('click', function () {
        var $checkbox = $(this);
        var $wrapper = $checkbox.parents('.paragraphs-ee-category-list-wrapper');
        if ($checkbox.is(':checked')) {
          $checkbox.parent().addClass('is-open');
          $('.paragraphs-ee-category-list', $wrapper).attr('aria-expanded', 'true');
        }
        else {
          $checkbox.parent().removeClass('is-open');
          $('.paragraphs-ee-category-list', $wrapper).attr('aria-expanded', 'false');
        }
      });
    }
  };

  /**
   * Resize open dialog based on current window width.
   */
  $(window).resize(function () {
    var $visibleDialogs = $('.ui-dialog.paragraphs-ee-add-dialog:visible');
    $visibleDialogs.each(function () {
      var $this = $(this);
      var dialog = $this.find('.ui-dialog-content').data('uiDialog');
      var dialogWidth = '90%';
      var windowWidth = $(window).width();
      var dialogMaxWidth = 1170;
      if (windowWidth > (dialogMaxWidth + 50)) {
        dialogWidth = dialogMaxWidth + 'px';
      }
      $this.css('max-width', dialogWidth);
      // Reposition dialog.
      dialog.option('position', dialog.options.position);
    });

  });

}(jQuery, Drupal, drupalSettings, once));
