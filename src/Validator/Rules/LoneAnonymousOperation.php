<?php declare(strict_types=1);

namespace GraphQL\Validator\Rules;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Validator\ValidationContext;

/**
 * Lone anonymous operation.
 *
 * A GraphQL document is only valid if when it contains an anonymous operation
 * (the query short-hand) that it contains only that one operation definition.
 */
class LoneAnonymousOperation extends ValidationRule
{
    public function getVisitor(ValidationContext $context): array
    {
        $operationCount = 0;

        return [
            NodeKind::DOCUMENT => static function (DocumentNode $node) use (&$operationCount): void {
                $operationCount = 0;
                foreach ($node->definitions as $definition) {
                    if (! ($definition instanceof OperationDefinitionNode)) {
                        continue;
                    }

                    ++$operationCount;
                }
            },
            NodeKind::OPERATION_DEFINITION => static function (OperationDefinitionNode $node) use (
                &$operationCount,
                $context
            ): void {
                if (null !== $node->name || $operationCount <= 1) {
                    return;
                }

                $context->reportError(
                    new Error(static::anonOperationNotAloneMessage(), [$node])
                );
            },
        ];
    }

    public static function anonOperationNotAloneMessage(): string
    {
        return 'This anonymous operation must be the only defined operation.';
    }
}
