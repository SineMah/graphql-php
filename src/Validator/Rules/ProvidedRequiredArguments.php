<?php declare(strict_types=1);

namespace GraphQL\Validator\Rules;

use GraphQL\Error\Error;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use GraphQL\Language\VisitorOperation;
use GraphQL\Validator\ValidationContext;

class ProvidedRequiredArguments extends ValidationRule
{
    public function getVisitor(ValidationContext $context): array
    {
        $providedRequiredArgumentsOnDirectives = new ProvidedRequiredArgumentsOnDirectives();

        return $providedRequiredArgumentsOnDirectives->getVisitor($context) + [
            NodeKind::FIELD => [
                'leave' => static function (FieldNode $fieldNode) use ($context): ?VisitorOperation {
                    $fieldDef = $context->getFieldDef();

                    if (null === $fieldDef) {
                        return Visitor::skipNode();
                    }

                    $argNodes = $fieldNode->arguments;

                    $argNodeMap = [];
                    foreach ($argNodes as $argNode) {
                        $argNodeMap[$argNode->name->value] = $argNode;
                    }

                    foreach ($fieldDef->args as $argDef) {
                        $argNode = $argNodeMap[$argDef->name] ?? null;
                        if (null !== $argNode || ! $argDef->isRequired()) {
                            continue;
                        }

                        $context->reportError(new Error(
                            static::missingFieldArgMessage($fieldNode->name->value, $argDef->name, $argDef->getType()->toString()),
                            [$fieldNode]
                        ));
                    }

                    return null;
                },
            ],
        ];
    }

    public static function missingFieldArgMessage(string $fieldName, string $argName, string $type): string
    {
        return "Field \"{$fieldName}\" argument \"{$argName}\" of type \"{$type}\" is required but not provided.";
    }
}
