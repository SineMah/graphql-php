<?php declare(strict_types=1);

namespace GraphQL\Examples\Blog\Type\Scalar;

use const FILTER_VALIDATE_URL;
use function filter_var;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use function is_string;

class UrlType extends ScalarType
{
    public function serialize($value): string
    {
        if (! $this->isUrl($value)) {
            throw new SerializationError('Cannot represent value as URL: ' . Utils::printSafe($value));
        }

        return $value;
    }

    public function parseValue($value): string
    {
        if (! $this->isUrl($value)) {
            throw new Error('Cannot represent value as URL: ' . Utils::printSafe($value));
        }

        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        // Throwing GraphQL\Error\Error to benefit from GraphQL error location in query
        if (! ($valueNode instanceof StringValueNode)) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }

        $value = $valueNode->value;
        if (! $this->isUrl($value)) {
            throw new Error('Query error: Not a valid URL', [$valueNode]);
        }

        return $value;
    }

    /**
     * Is the given value a valid URL?
     *
     * @param mixed $value
     */
    private function isUrl($value): bool
    {
        return is_string($value)
            && false !== filter_var($value, FILTER_VALIDATE_URL);
    }
}
