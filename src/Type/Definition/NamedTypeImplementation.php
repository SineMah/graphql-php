<?php declare(strict_types=1);

namespace GraphQL\Type\Definition;

use function array_key_exists;
use function preg_replace;
use ReflectionClass;

/**
 * @see NamedType
 */
trait NamedTypeImplementation
{
    public string $name;

    public ?string $description;

    public function toString(): string
    {
        return $this->name;
    }

    protected function tryInferName(): ?string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        // If class is extended - infer name from className
        // QueryType -> Type
        // SomeOtherType -> SomeOther
        $reflection = new ReflectionClass($this);
        $name = $reflection->getShortName();

        if (__NAMESPACE__ !== $reflection->getNamespaceName()) {
            return preg_replace('~Type$~', '', $name);
        }

        return null;
    }

    public function isBuiltInType(): bool
    {
        return array_key_exists($this->name, Type::getAllBuiltInTypes());
    }
}
