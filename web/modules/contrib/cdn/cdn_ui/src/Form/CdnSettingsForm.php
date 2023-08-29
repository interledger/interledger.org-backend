<?php

declare(strict_types = 1);

namespace Drupal\cdn_ui\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure CDN settings for this site.
 */
class CdnSettingsForm extends ValidatableConfigFormBase {

  /**
   * The 'nocssjs' conditions preset: all files except CSS and JS.
   *
   * @var array
   */
  private const CONDITIONS_PRESET_NOCSSJS = [
    'not' => [
      'extensions' => ['css', 'js'],
    ],
  ];

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, StreamWrapperManagerInterface $streamWrapperManager) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->streamWrapperManager = $streamWrapperManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cdn_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cdn.settings'];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getMainConfigName() : string {
    return 'cdn.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cdn.settings');

    $form['cdn_settings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-mapping',
      '#attached' => [
        'library' => [
          'cdn_ui/summaries',
        ],
      ],
    ];

    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Status'),
      '#group' => 'cdn_settings',
    ];
    $form['status']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Serve files from CDN'),
      '#description' => $this->t('Better performance thanks to better caching of files by the visitor. When a file changes a different URL is used, to ensure instantaneous updates for your visitors.'),
      '#default_value' => $config->get('status'),
    ];

    $form['mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Mapping'),
      '#group' => 'cdn_settings',
      '#tree' => TRUE,
    ];

    $mapping_type_ui_string = $this->t('Use @mapping-type mapping');
    [$mapping_type_ui_string_prefix, $mapping_type_ui_string_suffix] = explode('@mapping-type', (string) $mapping_type_ui_string, 2);
    $form['mapping']['type'] = [
      '#field_prefix' => $mapping_type_ui_string_prefix,
      '#field_suffix' => $mapping_type_ui_string_suffix,
      '#type' => 'select',
      '#title' => $this->t('Mapping type'),
      '#title_display' => 'invisible',
      '#options' => [
        'simple' => $this->t('simple'),
        'advanced' => $this->t('advanced'),
      ],
      '#required' => TRUE,
      '#wrapper_attributes' => ['class' => ['container-inline']],
      '#attributes' => ['class' => ['container-inline']],
      '#default_value' => $config->get('mapping.type') === 'simple' ?: 'advanced',
    ];
    $form['mapping']['simple'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="mapping[type]"]' => ['value' => 'simple'],
        ],
      ],
      '#attributes' => ['class' => ['container-inline']],
    ];
    $simple_mapping_ui_string = $this->t('Serve @files-with-some-extension from @domain using @type-of-urls');
    [
      $simple_mapping_ui_string_part_one,
      $simple_mapping_ui_string_part_two,
      $simple_mapping_ui_string_part_three,
    ] = preg_split('/\@[a-z\-]+/', (string) $simple_mapping_ui_string, -1, PREG_SPLIT_NO_EMPTY);
    $form['mapping']['simple']['extensions_condition_toggle'] = [
      '#type' => 'select',
      '#title' => $this->t('Limit by file extension'),
      '#title_display' => 'invisible',
      '#field_prefix' => $simple_mapping_ui_string_part_one,
      '#options' => [
        'all' => $this->t('all files'),
        'nocssjs' => $this->t('all files except CSS+JS'),
        'limited' => $this->t('only files'),
      ],
      '#default_value' => $config->get('mapping.conditions') === static::CONDITIONS_PRESET_NOCSSJS
        ? 'nocssjs'
        : (empty($config->get('mapping.conditions.extensions')) ? 'all' : 'limited'),
    ];
    $form['mapping']['simple']['extensions_condition_value'] = [
      '#field_prefix' => $this->t('with the extension'),
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#title_display' => 'invisible',
      '#placeholder' => 'jpg jpeg png zip',
      '#size' => 30,
      '#default_value' => implode(' ', $config->get('mapping.conditions.extensions') ?: []),
      '#states' => [
        'visible' => [
          ':input[name="mapping[simple][extensions_condition_toggle]"]' => ['value' => 'limited'],
        ],
      ],
    ];
    $form['mapping']['simple']['domain'] = [
      '#field_prefix' => $simple_mapping_ui_string_part_two,
      '#type' => 'textfield',
      '#placeholder' => 'example.com',
      '#title' => $this->t('Domain'),
      '#title_display' => 'FALSE',
      '#size' => 25,
      '#default_value' => $config->get('mapping.domain'),
    ];
    $form['mapping']['advanced'] = [
      '#type' => 'item',
      '#markup' => '<em>' . $this->t('Not configurable through the UI. Modify <code>cdn.settings.yml</code> directly, and <a href=":url">import it</a>. It is safe to edit all other settings via the UI.', [':url' => 'https://www.drupal.org/documentation/administer/config']) . '</em>',
      '#states' => [
        'visible' => [
          ':input[name="mapping[type]"]' => ['value' => 'advanced'],
        ],
      ],
    ];
    $form['mapping']['simple']['scheme'] = [
      '#field_prefix' => $simple_mapping_ui_string_part_three,
      '#type' => 'select',
      '#title' => $this->t('Type of URLs'),
      '#title_display' => 'FALSE',
      '#options' => [
        '//' => $this->t('scheme-relative URLs'),
        'https://' => $this->t('HTTPS URLs'),
        'http://' => $this->t('HTTP URLs'),
      ],
      '#default_value' => $config->get('scheme'),
    ];

    $form['farfuture'] = [
      '#type' => 'details',
      '#title' => $this->t('Forever cacheable files'),
      '#group' => 'cdn_settings',
      '#tree' => TRUE,
    ];
    $form['farfuture']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make files cacheable forever'),
      '#description' => $this->t('Better performance thanks to better caching of files by the visitor. When a file changes a different URL is used, to ensure instantaneous updates for your visitors.'),
      '#default_value' => $config->get('farfuture.status'),
    ];

    $visible_stream_wrappers = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::VISIBLE);
    $non_core_visible_stream_wrappers = array_filter($visible_stream_wrappers, function (array $metadata) {
      return strpos($metadata['class'], 'Drupal\Core') !== 0;
    });
    $form['wrappers'] = [
      '#type' => 'details',
      '#title' => $this->t('Stream wrappers'),
      '#group' => 'cdn_settings',
      '#tree' => TRUE,
      '#access' => !empty($non_core_visible_stream_wrappers),
    ];
    $checkboxes = $this->buildStreamWrapperCheckboxes(array_keys($visible_stream_wrappers));
    $form['wrappers']['stream_wrappers'] = [
      '#type' => 'checkboxes',
      '#options' => array_combine(array_keys($checkboxes), array_keys($checkboxes)),
      '#default_value' => $config->get('stream_wrappers'),
      '#description' => $this->t('Stream wrappers whose files to serve from CDN. Any stream wrapper generating local file URLs is eligible.'),
    ];
    $form['wrappers']['stream_wrappers'] += $checkboxes;
    // Special case: private://.
    if (!empty($form['wrappers']['stream_wrappers']['private'])) {
      $form['wrappers']['stream_wrappers']['private']['#disabled'] = TRUE;
      $form['wrappers']['stream_wrappers']['private']['#title'] = '<del>' . $form['wrappers']['stream_wrappers']['private']['#title'] . '</del>';
      $form['wrappers']['stream_wrappers']['private']['#description'] = $this->t('Private files require authentication and hence cannot be served from a CDN.');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Determines whether the stream wrapper generates external URLs.
   *
   * @param string $stream_wrapper_scheme
   *   A valid stream wrapper scheme.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperInterface $stream_wrapper
   *   A stream wrapper instance.
   *
   * @return bool
   */
  protected function streamWrapperGeneratesExternalUrls($stream_wrapper_scheme, StreamWrapperInterface $stream_wrapper) : bool {
    // Generate URL to imaginary file 'cdn.test'. Most stream wrappers don't
    // check file existence, just concatenate strings.
    $stream_wrapper->setUri($stream_wrapper_scheme . '://cdn.test');
    try {
      $absolute_url = $stream_wrapper->getExternalUrl();
      $base_url = $this->getRequest()->getSchemeAndHttpHost() . $this->getRequest()->getBasePath();
      $relative_url = str_replace($base_url, '', $absolute_url);
      return UrlHelper::isExternal($relative_url);
    }
    catch (\Exception $e) {
      // In case of failure, assume this would have resulted in an external URL.
      return TRUE;
    }
  }

  /**
   * Builds the stream wrapper checkboxes form array.
   *
   * @param string[] $stream_wrapper_schemes
   *   The stream wrapper schemes for which to generate form checkboxes.
   *
   * @return array
   */
  protected function buildStreamWrapperCheckboxes(array $stream_wrapper_schemes) : array {
    $checkboxes = [];
    foreach ($stream_wrapper_schemes as $stream_wrapper_scheme) {
      $wrapper = $this->streamWrapperManager->getViaScheme($stream_wrapper_scheme);
      $generates_external_urls = static::streamWrapperGeneratesExternalUrls($stream_wrapper_scheme, $wrapper);
      $checkboxes[$stream_wrapper_scheme] = [
        '#title' => $this->t('@name → <code>@scheme://</code>', [
          '@scheme' => $stream_wrapper_scheme,
          '@name' => $wrapper->getName(),
        ]),
        '#disabled' => $generates_external_urls,
        '#description' => !$generates_external_urls ? NULL : $this->t('This stream wrapper generates external URLs, and hence cannot be served from a CDN.'),
      ];
    }
    return $checkboxes;
  }

  /**
   * {@inheritdoc}
   */
  protected static function mapFormValuesToConfig(FormStateInterface $form_state, Config $config) {
    // Vertical tab: 'Status'.
    $config->set('status', (bool) $form_state->getValue('status'));

    // Vertical tab: 'Stream wrappers'.
    $stream_wrappers = array_values(array_filter($form_state->getValue([
      'wrappers',
      'stream_wrappers',
    ])));
    $config->set('stream_wrappers', $stream_wrappers);

    // Vertical tab: 'Mapping'.
    if ($form_state->getValue(['mapping', 'type']) === 'simple') {
      $simple_mapping = $form_state->getValue(['mapping', 'simple']);
      $config->set('mapping', []);
      $config->set('mapping.type', 'simple');
      $config->set('mapping.domain', $simple_mapping['domain']);
      // Only the 'extensions' condition is supported in this UI, to KISS.
      if ($simple_mapping['extensions_condition_toggle'] === 'limited') {
        // Set the 'extensions' condition unconditionally.
        $config->set('mapping.conditions.extensions', explode(' ', trim($simple_mapping['extensions_condition_value'])));
      }
      // Plus one particular common preset: 'nocssjs', which means all files
      // except CSS and JS.
      elseif ($simple_mapping['extensions_condition_toggle'] === 'nocssjs') {
        $config->set('mapping.conditions', static::CONDITIONS_PRESET_NOCSSJS);
      }
      else {
        // Remove the 'not' or 'extensions' conditions if set.
        $conditions = $config->getOriginal('mapping.type') === 'simple' ? $config->getOriginal('mapping.conditions') : [];
        if (isset($conditions['not'])) {
          unset($conditions['not']);
        }
        if (isset($conditions['extensions'])) {
          unset($conditions['extensions']);
        }
        $config->set('mapping.conditions', $conditions);
      }
      $config->set('scheme', $simple_mapping['scheme']);
    }

    // Vertical tab: 'Forever cacheable files'.
    $config->set('farfuture.status', (bool) $form_state->getValue([
      'farfuture',
      'status',
    ]));

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  protected static function mapViolationPropertyPathsToFormNames(string $property_path) : string {
    switch ($property_path) {
      case 'mapping.domain':
        return 'mapping][simple][domain';

      case 'scheme':
        return 'mapping][simple][scheme';

      default:
        return parent::mapViolationPropertyPathsToFormNames($property_path);
    }
  }

}
