<?php

namespace Drupal\webform_rest\Plugin\rest\resource;

use Drupal\Core\Render\RendererInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Creates a resource for submitting a webform.
 *
 * @RestResource(
 *   id = "webform_rest_submit",
 *   label = @Translation("Webform Submit"),
 *   uri_paths = {
 *     "create" = "/webform_rest/submit"
 *   }
 * )
 */
class WebformSubmitResource extends ResourceBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->request = $container->get('request_stack');
    $instance->renderer = $container->get('renderer');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * Responds to entity POST requests and saves the new entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function post() {
    $webform_data = $this->request->getCurrentRequest()->getContent();
    if (empty($webform_data)) {
      $errors = [
        'error' => [
          'message' => $this->t('No data has been submitted.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }
    $webform_data = json_decode($webform_data, TRUE);

    // Basic check for webform ID.
    if (empty($webform_data['webform_id'])) {
      $errors = [
        'error' => [
          'message' => $this->t('Missing required webform_id value.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }

    // Convert to webform values format.
    $values = [
      'webform_id' => $webform_data['webform_id'],
      'entity_type' => NULL,
      'entity_id' => NULL,
      'uri' => $this->request->getCurrentRequest()->headers->get('referer')
    ];

    $values['data'] = $webform_data;

    // Don't submit webform ID.
    unset($values['data']['webform_id']);

    // Check for a valid webform.
    $webform = Webform::load($values['webform_id']);

    //Check if webform allows drafts
    $allow_draft = $webform->getSetting('draft');
      if(isset($webform_data['draft']) && $allow_draft === 'none' && $webform_data['draft'] === TRUE){
        $errors = [
          'error' => [
            'message' => $this->t('This webform does not allow draft submissions.'),
          ],
        ];
      return new ModifiedResourceResponse($errors, 400);
    }
    
    if (isset($webform_data['draft'])) {
      $values['in_draft'] = $webform_data['draft'] !== TRUE ? FALSE : TRUE;
    }

    if (!$webform) {
      $errors = [
        'error' => [
          'message' => $this->t('Invalid webform_id value.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }

    // Check webform is open.
    $is_open = WebformSubmissionForm::isOpen($webform);

    if ($is_open === TRUE) {
      // Validate submission.
      $errors = WebformSubmissionForm::validateFormValues($values);

      // Check there are no validation errors.
      if (!empty($errors)) {
        return new ModifiedResourceResponse([
          'message' => $this->t('Submitted Data contains validation errors.'),
          'error'   => $errors,
        ], 400);
      }
      else {
        // Return submission UUID.
        $webform_submission = WebformSubmissionForm::submitFormValues($values);
        // Prepare response
        $response = ['sid' => $webform_submission->uuid()];
        $send_confirmation_settings = $this->configFactory->get('webform_rest.settings')->get('confirmation_settings');
        if($send_confirmation_settings){
          $response += [
            'confirmation_type' => $webform->getSetting('confirmation_type'),
            'confirmation_url' => $webform->getSetting('confirmation_url'),
            'confirmation_message' => $webform->getSetting('confirmation_message'),
            'confirmation_title' => $webform->getSetting('confirmation_title'),
          ];
        }
        return new ModifiedResourceResponse($response);
      }
    }
    else {
      $errors = [
        'error' => [
          'message' => $this->renderer->renderPlain($is_open),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }
  }

}
