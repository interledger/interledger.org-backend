(function ($, Drupal) {
  Drupal.behaviors.adminToolbar = {
    attach: function (context, settings) {

      $('a.toolbar-icon', context).removeAttr('title');

      // Get keyboard functionality from mobile menu.
      if ('drupalToolbarMenu' in $.fn) {
        $(once('keyboard-navigation', '.toolbar-menu-administration')).each(function () {
          const $toolbar = $(this);

          // Initialize Extend/Collapse buttons even if menu was loaded horizontally.
          $toolbar.children('.toolbar-menu').drupalToolbarMenu()

          // Don't automatically open active page's menu if not on mobile.
          function closeActiveTrail() {
            if (!$toolbar.closest('.toolbar-tray-horizontal').length) {
              return;
            }
            $toolbar.find('.menu-item--active-trail.open, .menu-item--active-trail .open').each(function () {
              this.classList.remove('open');
            })
          }
          closeActiveTrail();

          // Watch for addition/removal of 'open' class.
          const classObserver = new MutationObserver((mutations) => {
            mutations.forEach(mu => {
              if (mu.type !== "attributes" && mu.attributeName !== "class") {
                return;
              }

              const classList = mu.target.classList;
              const oldClassList = mu.oldValue.split(' ');

              // If menu was opened using button, add 'hover-intent' class and prevent hover-out from closing.
              if (classList.contains('open') && !classList.contains('hover-intent')
                && (!oldClassList.includes('open') || oldClassList.includes('hover-intent'))) {
                classList.add('hover-intent');
                return;
              }

              // When menu is closed using button, remove 'hover-intent' class.
              if (!classList.contains('open') && oldClassList.includes('open')
                && classList.contains('hover-intent')) {
                classList.remove('hover-intent');
                return;
              }
            });
          });

          $toolbar.find('.menu-item--expanded').each(function () {
            const li = this;
            classObserver.observe((li), { attributes: true, attributeOldValue: true });
          });
        });
      }

      $(once('dismiss-menus', '.toolbar-menu-administration')).each(function () {
        const $toolbar = $(this);

        function dismissOpenMenus() {
          if (!$toolbar.closest('.toolbar-tray-horizontal').length) {
            return;
          }
          $toolbar.find('.open').each(function () {
            this.classList.remove('open');
          });
          $toolbar.find('.hover-intent').each(function () {
            this.classList.remove('hover-intent');
          });
        }

        // Dismiss any open menus by pressing Escape key.
        $(document).keyup(function (e) {
          if (e.which !== 27) {
            return;
          }
          dismissOpenMenus();
        });

        // Dismiss any open menus by clicking out.
        $(document).click(function (e) {
          if ($(e.target).closest('#toolbar-item-administration-tray').length) {
            return;
          }
          dismissOpenMenus();
        });
      });

      // Fix padding at top of body when horizontal toolbar wraps.
      $(once('update-toolbar-height', 'body')).each(function () {
        // Set timer so padding doesn't update continuously on resize.
        let timer;
        jQuery(window).on('resize', function () {
          clearTimeout(timer);
          timer = setTimeout(Drupal.toolbar.ToolbarVisualView.prototype.updateToolbarHeight.bind(Drupal.toolbar.views.toolbarVisualView), 100);
        });
      })

      $('.toolbar-menu:first-child > .menu-item:not(.menu-item--expanded) a, .toolbar-tab > a', context).on('focusin', function () {
        $('.menu-item--expanded').removeClass('hover-intent');
      });

      $('ul:not(.toolbar-menu)', context).on({
        mousemove: function () {
          $('li.menu-item--expanded').removeClass('hover-intent');
        },
        hover: function () {
          $('li.menu-item--expanded').removeClass('hover-intent');
        }
      });

      // Always hide the dropdown menu on mobile.
      if (window.matchMedia("(max-width: 767px)").matches && $('body').hasClass('toolbar-tray-open')) {
        $('body').removeClass('toolbar-tray-open');
        $('#toolbar-item-administration').removeClass('is-active');
        $('#toolbar-item-administration-tray').removeClass('is-active');
      };

    }
  };
})(jQuery, Drupal);
