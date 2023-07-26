<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "SchemaInformation",
 * )
 */
class SchemaInformationType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Schema information provided by the system.'),
      'fields' => function () {
        $settings = $this->configFactory->get('graphql_compose.settings');

        $fields = [
          'version' => [
            'type' => Type::string(),
            'description' => (string) $this->t('The schema version.'),
          ],
          'description' => [
            'type' => Type::string(),
            'description' => (string) $this->t('The schema description.'),
          ],
        ];

        if ($settings->get('settings.site_name')) {
          $fields['name'] = [
            'type' => Type::string(),
            'description' => (string) $this->t('The site name.'),
          ];
        }

        if ($settings->get('settings.site_slogan')) {
          $fields['slogan'] = [
            'type' => Type::string(),
            'description' => (string) $this->t('The site slogan.'),
          ];
        }

        if ($settings->get('settings.site_front')) {
          $fields['home'] = [
            'type' => Type::string(),
            'description' => (string) $this->t('The internal path to the front page.'),
          ];
        }

        ksort($fields);

        return $fields;
      },
    ]);

    return $types;
  }

}
