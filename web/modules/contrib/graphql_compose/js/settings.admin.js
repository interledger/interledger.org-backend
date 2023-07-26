/**
 * @file
 * Defines JavaScript behaviors for the graphql compose settings form.
 */

(($, Drupal) => {
  /**
   * Behaviors for summaries for tabs in the graphql compose settings form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the graphql compose settings form.
   */
  Drupal.behaviors.graphqlComposeFormSummaries = {
    attach(context) {
      $('.entity-type-tab', context).drupalSetSummary((tab) => {
        const enabled = $('input.entity-bundle-enabled:checked', tab);
        return enabled.length > 0 ? Drupal.t('Enabled') : '';
      });

      $('.entity-bundle-tab', context).drupalSetSummary((tab) => {
        const enabled = $('input.entity-bundle-enabled:checked', tab);
        return enabled.length > 0 ? Drupal.t('Enabled') : '';
      });

      // Open nodes by default.
      $('a[href="#edit-layout-entity-tabs-node"]', context).trigger('click');
    },
  };
})(jQuery, Drupal);
