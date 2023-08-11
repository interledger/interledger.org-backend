/**
 * @file
 * GraphQL Compose: Copy UUID to clipboard.
 */

((Drupal) => {
  /**
   * Add a utility UUID copy with toast feedback.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach clipboard copy to link click.
   */
  Drupal.behaviors.graphqlComposeCopyUuid = {
    attach(context) {
      once('copyUuid', '.graphql-compose--uuid-link', context).forEach(
        (element) => {
          const addToast = () => {
            const toast = document.createElement('div');
            toast.setAttribute('role', 'alert');
            toast.classList.add('graphql-compose--uuid-toast');
            toast.innerText = Drupal.t('UUID copied to clipboard');
            document.body.appendChild(toast);

            const styles = {
              position: 'fixed',
              bottom: '1rem',
              right: '1rem',
              padding: '0.5rem 1rem',
              background: '#000',
              color: '#fff',
              borderRadius: '6px',
              zIndex: '9999',
              opacity: 0,
              fontSize: '0.875rem',
              transition: 'opacity 0.3s ease-in-out',
            };

            Object.assign(toast.style, styles);

            setTimeout(() => {
              toast.style.opacity = 1;
            }, 10);

            setTimeout(() => {
              toast.style.opacity = 0;
            }, 3000);
          };

          /**
           * Workaround for non https sites.
           *
           * @param {content} content
           *   The text to be copied to the clipboard
           */
          const unsecuredCopyToClipboard = (content) => {
            const textArea = document.createElement('textarea');
            textArea.value = content;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
              document.execCommand('copy');
            } catch (err) {
              throw new Error('Unable to copy to clipboard');
            }
            document.body.removeChild(textArea);
          };

          /**
           * Copies the text passed as param to the system clipboard
           * Check if using HTTPS and navigator.clipboard is available
           * Then uses standard clipboard API, otherwise uses fallback
           *
           * @param {string} content
           *  The text to be copied to the clipboard
           */
          const copyToClipboard = (content) => {
            if (window.isSecureContext && navigator.clipboard) {
              navigator.clipboard.writeText(content);
            } else {
              unsecuredCopyToClipboard(content);
            }
          };

          element.addEventListener('click', (e) => {
            e.preventDefault();
            copyToClipboard(e.currentTarget.dataset.uuid);
            e.currentTarget.blur();
            addToast();
          });
        },
      );
    },
  };
})(Drupal);
