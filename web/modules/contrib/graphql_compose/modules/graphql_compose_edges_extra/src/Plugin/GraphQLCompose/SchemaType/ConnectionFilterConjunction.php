<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\EnumType;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ConnectionFilterConjunction",
 * )
 */
class ConnectionFilterConjunction extends GraphQLComposeSchemaTypeBase
{

    /**
     * {@inheritDoc}
     */
    public function getTypes(): array
    {
        $types = [];

        $conjunctions = [
            'AND' => [
                'value' => 'AND',
                'description' => (string) $this->t('AND conjuction'),
            ],
            'OR' => [
                'value' => 'OR',
                'description' => (string) $this->t('OR conjuction'),
            ],
        ];

        $types[] = new EnumType([
            'name' => $this->getPluginId(),
            'description' => (string) $this->t('Conjunctions for filter '),
            'values' => $conjunctions,
        ]);

        return $types;
    }
}
