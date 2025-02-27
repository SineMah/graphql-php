<?php declare(strict_types=1);

namespace GraphQL\Validator\Rules;

use GraphQL\Error\Error;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Validator\ValidationContext;

class QueryDepth extends QuerySecurityRule
{
    protected int $maxQueryDepth;

    public function __construct(int $maxQueryDepth)
    {
        $this->setMaxQueryDepth($maxQueryDepth);
    }

    public function getVisitor(ValidationContext $context): array
    {
        return $this->invokeIfNeeded(
            $context,
            [
                NodeKind::OPERATION_DEFINITION => [
                    'leave' => function (OperationDefinitionNode $operationDefinition) use ($context): void {
                        $maxDepth = $this->fieldDepth($operationDefinition);

                        if ($maxDepth <= $this->maxQueryDepth) {
                            return;
                        }

                        $context->reportError(
                            new Error(static::maxQueryDepthErrorMessage($this->maxQueryDepth, $maxDepth))
                        );
                    },
                ],
            ]
        );
    }

    protected function fieldDepth(Node $node, int $depth = 0, int $maxDepth = 0): int
    {
        if (isset($node->selectionSet) && $node->selectionSet instanceof SelectionSetNode) {
            foreach ($node->selectionSet->selections as $childNode) {
                $maxDepth = $this->nodeDepth($childNode, $depth, $maxDepth);
            }
        }

        return $maxDepth;
    }

    protected function nodeDepth(Node $node, int $depth = 0, int $maxDepth = 0): int
    {
        switch (true) {
            case $node instanceof FieldNode:
                // node has children?
                if (null !== $node->selectionSet) {
                    // update maxDepth if needed
                    if ($depth > $maxDepth) {
                        $maxDepth = $depth;
                    }

                    $maxDepth = $this->fieldDepth($node, $depth + 1, $maxDepth);
                }

                break;

            case $node instanceof InlineFragmentNode:
                $maxDepth = $this->fieldDepth($node, $depth, $maxDepth);

                break;

            case $node instanceof FragmentSpreadNode:
                $fragment = $this->getFragment($node);

                if (null !== $fragment) {
                    $maxDepth = $this->fieldDepth($fragment, $depth, $maxDepth);
                }

                break;
        }

        return $maxDepth;
    }

    /**
     * Set max query depth. If equal to 0 no check is done. Must be greater or equal to 0.
     */
    public function setMaxQueryDepth(int $maxQueryDepth): void
    {
        $this->checkIfGreaterOrEqualToZero('maxQueryDepth', $maxQueryDepth);

        $this->maxQueryDepth = $maxQueryDepth;
    }

    public static function maxQueryDepthErrorMessage(int $max, int $count): string
    {
        return "Max query depth should be {$max} but got {$count}.";
    }

    protected function isEnabled(): bool
    {
        return self::DISABLED !== $this->maxQueryDepth;
    }
}
