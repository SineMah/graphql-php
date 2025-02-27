<?php declare(strict_types=1);

namespace GraphQL\Validator\Rules;

use function count;
use GraphQL\Error\Error;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Visitor;
use GraphQL\Language\VisitorOperation;
use GraphQL\Validator\ValidationContext;

class SingleFieldSubscription extends ValidationRule
{
    /**
     * @return array<string, callable>
     */
    public function getVisitor(ValidationContext $context): array
    {
        return [
            NodeKind::OPERATION_DEFINITION => static function (OperationDefinitionNode $node) use ($context): VisitorOperation {
                if ('subscription' === $node->operation) {
                    $selections = $node->selectionSet->selections;

                    if (count($selections) > 1) {
                        $offendingSelections = $selections->splice(1, count($selections));

                        $context->reportError(new Error(
                            static::multipleFieldsInOperation($node->name->value ?? null),
                            $offendingSelections
                        ));
                    }
                }

                return Visitor::skipNode();
            },
        ];
    }

    public static function multipleFieldsInOperation(?string $operationName): string
    {
        if (null === $operationName) {
            return 'Anonymous Subscription must select only one top level field.';
        }

        return "Subscription \"{$operationName}\" must select only one top level field.";
    }
}
