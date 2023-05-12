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
    const [table] = once('paragraphs-features-add-in-between-init', '.field-multiple-table', context);
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
      const buttonsAllCount = Array.from(dialog.querySelectorAll('input, button')).length;
      const addButtons = Array.from(dialog.querySelectorAll('input[data-easy-access-weight], button[data-easy-access-weight]'));


      addButtons.slice(0, field.linkCount).forEach((addButton) => {
        // Create a remote button triggering original add button in dialog.
        const button = Drupal.theme('paragraphsFeaturesAddInBetweenButton', {title: addButton.value});
        // Set title attribute.
        button.setAttribute('title', Drupal.t('Add @title', {'@title': addButton.value}, {context: 'Paragraphs Editor Enhancements'}));
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
          Drupal.t('...', {}, {context: 'Paragraphs Features'}) :
          Drupal.t('+ Add', {}, {context: 'Paragraphs Features'});
        const button = Drupal.theme('paragraphsFeaturesAddInBetweenMoreButton', {title: title, settings: dialog.dataset});

        Drupal.paragraphs_ee.addEventListenerToMoreButton(button);
        buttons.push(button);
      }

      // First item needs a special class.
      buttons[0].classList.add('first');
      // The last button in the list needs a special class.
      buttons[buttons.length - 1].classList.add('last');

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

      const rowSelector = '.paragraphs-features__add-in-between__row:not(:first-of-type):not(:last-of-type)';
      var $self = $(rowElement);
      $self.on('mouseover', function () {
        $self.prev(rowSelector).find('.paragraphs-features__add-in-between__wrapper').css({'opacity': '1.0'});
        $self.next(rowSelector).find('.paragraphs-features__add-in-between__wrapper').css({'opacity': '1.0'});
      });
      $self.on('mouseout', function () {
        $self.prev(rowSelector).find('.paragraphs-features__add-in-between__wrapper').css({'opacity': '0.0'});
        $self.next(rowSelector).find('.paragraphs-features__add-in-between__wrapper').css({'opacity': '0.0'});
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
      Drupal.ajax.bindAjaxLinksWithProgress(tableBody.querySelector('.paragraphs-features__add-in-between__wrapper'));
    }
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
      button.setAttribute('data-dialog-options', '{"width":485}');
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
   * @todo Remove if https://www.drupal.org/project/drupal/issues/2818463 has
   *   been committed.
   */
  Drupal.ajax.bindAjaxLinksWithProgress = function (element) {
    once('paragraphs-ee-ajax', '.use-ajax', element).forEach(function (i, ajaxLink) {
      var $linkElement = $(ajaxLink);

      var elementSettings = {
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
    // Deep clone with all attached events. We need to work on cloned element
    // and not directly on origin because Drupal dialog.ajax.js
    // Drupal.behaviors.dialog will do remove of origin element on dialog close.
    var default_options = {
      // Turn off autoResize from dialog.position so draggable is not disabled.
      autoResize: true,
      resizable: false,
      dialogClass: 'paragraphs-add-dialog--categorized paragraphs-style-' + dialogStyle,
      title: title,
      width: '720px',
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
  };

}(jQuery, Drupal, drupalSettings, once));
