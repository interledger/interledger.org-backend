<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ConnectionFilterCondition",
 * )
 */
class ConnectionFilterCondition extends GraphQLComposeSchemaTypeBase
{

    /**
     * {@inheritDoc}
     */
    public function getTypes(): array
    {
        $types = [];

        $types[] = new InputObjectType([
            'name' => $this->getPluginId(),
            'description' => (string) $this->t('Filter conditions'),
            'fields' => fn () => [
                'enabled' => Type::boolean(),
                'field' => Type::nonNull(Type::string()),
                'language' => Type::string(),
                'operator' => static::type('ConnectionFilterOperator'),
                'value' => Type::listOf(Type::string())
            ],
        ]);

        return $types;
    }
}
