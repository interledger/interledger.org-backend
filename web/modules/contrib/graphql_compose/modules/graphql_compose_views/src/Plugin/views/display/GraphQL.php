<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\views\display;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use function Symfony\Component\String\u;

/**
 * Provides a display plugin for GraphQL views.
 *
 * @ViewsDisplay(
 *   id = "graphql",
 *   title = @Translation("GraphQL"),
 *   help = @Translation("Creates a GraphQL entity list display."),
 *   admin = @Translation("GraphQL"),
 *   graphql_display = TRUE,
 *   returns_response = TRUE
 * )
 */
class GraphQL extends DisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesAJAX = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesPager = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesMore = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAreas = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return 'graphql';
  }

  /**
   * {@inheritdoc}
   */
  public function usesFields(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displaysExposed(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();

    // Set the default plugins to 'graphql'.
    $options['style']['contains']['type']['default'] = 'graphql';
    $options['exposed_form']['contains']['type']['default'] = 'graphql';
    $options['row']['contains']['type']['default'] = 'graphql_entity';

    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['exposed_form'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    // Remove css/exposed form settings,
    // as they are not used for the data display.
    unset($options['exposed_block']);
    unset($options['css_class']);

    $options['graphql_query_name'] = ['default' => ''];
    $options['graphql_query_exposed'] = ['default' => TRUE];

    return $options;
  }

  /**
   * Get the user defined query name or the default one.
   *
   * @return string
   *   The query name.
   */
  public function getGraphQlQueryName(): string {
    return Unicode::lcfirst($this->getGraphQlName());
  }

  /**
   * Gets the result name.
   *
   * @return string
   *   The result name.
   */
  public function getGraphQlResultName(): string {
    return $this->getGraphQlName('result');
  }

  /**
   * Gets the row name.
   *
   * @return string
   *   The row name.
   */
  public function getGraphQlRowName(): string {
    return $this->getGraphQlName('row');
  }

  /**
   * Gets the filter input name.
   *
   * @return string
   *   The filter input name.
   */
  public function getGraphQlFilterInputName(): string {
    return $this->getGraphQlName('filter_input');
  }

  /**
   * Gets the contextual filter input name.
   *
   * @return string
   *   The contextual filter input name.
   */
  public function getGraphQlContextualFilterInputName(): string {
    return $this->getGraphQlName('contextual_filter_input');
  }

  /**
   * Gets the sort input name.
   *
   * @return string
   *   The filter sort name.
   */
  public function getGraphQlSortInputName(): string {
    return $this->getGraphQlName('sort_keys');
  }

  /**
   * Return a type string for usage in GraphQL.
   *
   * @param string|null $suffix
   *   Id suffix, eg. row, result.
   *
   * @return string
   *   The formatted name.
   */
  public function getGraphQlName($suffix = NULL): string {
    $queryName = strip_tags($this->getOption('graphql_query_name'));

    $view_id = $this->view->id();
    $display_id = $this->display['id'];

    $suffix = u($suffix ?: '')
      ->camel()
      ->title()
      ->toString();

    return u($queryName ?: $view_id . '_' . $display_id)
      ->camel()
      ->title()
      ->append($suffix)
      ->toString();
  }

  /**
   * Get sort enum values.
   *
   * @return array
   *   A keyed array of enums ready for GraphQL.
   */
  public function getGraphQlSortEnums(): array {
    $exposed_sorts = array_filter(
      $this->getOption('sorts') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    $result = [];
    foreach ($exposed_sorts as $sort) {
      $key = u($sort['expose']['field_identifier'])
        ->snake()
        ->upper()
        ->toString();

      $result[$key] = [
        'value' => $sort['expose']['field_identifier'],
        'description' => $sort['expose']['label'],
      ];
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options): void {
    parent::optionsSummary($categories, $options);

    unset($categories['title']);
    unset($categories['pager'], $categories['exposed'], $categories['access']);

    unset($options['show_admin_links'], $options['analyze-theme'], $options['link_display']);
    unset($options['show_admin_links'], $options['analyze-theme'], $options['link_display']);

    unset($options['title'], $options['access']);
    unset($options['exposed_block'], $options['css_class']);
    unset($options['query'], $options['group_by']);

    $categories['graphql'] = [
      'title' => $this->t('GraphQL'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    $options['graphql_query_name'] = [
      'category' => 'graphql',
      'title' => $this->t('Query name'),
      'value' => views_ui_truncate($this->getGraphQlQueryName(), 24),
    ];

    $options['graphql_query_exposed'] = [
      'category' => 'graphql',
      'title' => $this->t('Query visibility'),
      'value' => $this->getOption('graphql_query_exposed') ? $this->t('Visible') : $this->t('Hidden'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'graphql_query_name':
        $form['#title'] .= $this->t('Query name');

        $form['graphql_query_name'] = [
          '#type' => 'textfield',
          '#description' => $this->t('This will be the graphQL query name.'),
          '#default_value' => $this->getGraphQlQueryName(),
        ];

        break;

      case 'graphql_query_exposed':
        $form['#title'] .= $this->t('Query visible');

        $form['graphql_query_exposed'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Query visible'),
          '#description' => $this->t('
            Enable the query on the root of the schema.<br><br>
            Disabling hides the query only.<br>
            All types and resolvers are still added to your schema.<br><br>
            This is useful if you only want to use this view in a field with the <a href=":url" target="_blank">viewfield</a> module.
          ', [
            ':url' => 'https://www.drupal.org/project/viewfield',
          ]),
          '#default_value' => $this->getOption('graphql_query_exposed'),
        ];

        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'graphql_query_name':
        $this->setOption($section, $form_state->getValue($section));
        break;

      case 'graphql_query_exposed':
        $this->setOption($section, $form_state->getValue($section));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): bool {
    return $this->view->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $rows = (!empty($this->view->result) || $this->view->style_plugin->evenEmpty()) ? $this->view->style_plugin->render($this->view->result) : [];

    // Apply the cache metadata from the display plugin. This comes back as a
    // cache render array so we have to transform it back afterwards.
    $this->applyDisplayCacheabilityMetadata($this->view->element);

    return [
      'view' => $this->view,
      'rows' => $rows,
      'cache' => CacheableMetadata::createFromRenderArray($this->view->element),
    ];
  }

}
