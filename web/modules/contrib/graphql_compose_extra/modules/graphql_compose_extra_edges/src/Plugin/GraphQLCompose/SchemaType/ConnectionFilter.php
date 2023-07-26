<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_extra_edges\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ConnectionFilter",
 * )
 */
class ConnectionFilter extends GraphQLComposeSchemaTypeBase
{

    /**
     * {@inheritDoc}
     */
    public function getTypes(): array
    {
        $types = [];

        $types[] = new InputObjectType([
            'name' => $this->getPluginId(),
            'description' => (string) $this->t('Filter'),
            'fields' => fn () => [
                'conditions' => Type::listOf(static::type('ConnectionFilterCondition')),
                'conjunction' => static::type('ConnectionFilterConjunction'),
                'groups' => Type::listOf(static::type($this->getPluginId())),
            ],
        ]);

        return $types;
    }
}
