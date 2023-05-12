<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\views\exposed_form;

use Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase;

/**
 * Exposed form plugin that prevents any rendering.
 *
 * @ViewsExposedForm(
 *   id = "graphql",
 *   title = @Translation("GraphQL"),
 *   help = @Translation("Prevents rendering of exposed forms")
 * )
 */
class GraphQL extends ExposedFormPluginBase {

}
