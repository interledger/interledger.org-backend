<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\EnumType;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ConnectionFilterOperator",
 * )
 */
class ConnectionFilterOperator extends GraphQLComposeSchemaTypeBase
{

    /**
     * {@inheritDoc}
     */
    public function getTypes(): array
    {
        $types = [];

        $operators = [
            'BETWEEN' => [
                'value' => 'BETWEEN',
                'description' => (string) $this->t('Between 2 values'),
            ],
            'EQUAL' => [
                'value' => '=',
                'description' => (string) $this->t('Equal to'),
            ],
            'GREATER_THAN' => [
                'value' => '>',
                'description' => (string) $this->t('Greater than'),
            ],
            'GREATER_THAN_OR_EQUAL' => [
                'value' => '>=',
                'description' => (string) $this->t('Greater than or equal'),
            ],
            'IN' => [
                'value' => 'IN',
                'description' => (string) $this->t('In'),
            ],
            'IS_NOT_NULL' => [
                'value' => 'IS NOT NULL',
                'description' => (string) $this->t('Is not null'),
            ],
            'IS_NULL' => [
                'value' => 'IS NULL',
                'description' => (string) $this->t('Is null'),
            ],
            'LIKE' => [
                'value' => 'LIKE',
                'description' => (string) $this->t('Like / contains'),
            ],
            'NOT_BETWEEN' => [
                'value' => 'NOT BETWEEN',
                'description' => (string) $this->t('Not between'),
            ],
            'NOT_EQUAL' => [
                'value' => '<>',
                'description' => (string) $this->t('Not equal'),
            ],
            'NOT_IN' => [
                'value' => 'NOT IN',
                'description' => (string) $this->t('Not in'),
            ],
            'NOT_LIKE' => [
                'value' => 'NOT LIKE',
                'description' => (string) $this->t('Not like'),
            ],
            'SMALLER_THAN' => [
                'value' => '<',
                'description' => (string) $this->t('Smaller than'),
            ],
            'SMALLER_THAN_OR_EQUAL' => [
                'value' => '<=',
                'description' => (string) $this->t('Smaller than or equal'),
            ],
        ];


        $types[] = new EnumType([
            'name' => $this->getPluginId(),
            'description' => (string) $this->t('Operators for filter '),
            'values' => $operators,
        ]);

        return $types;
    }
}
