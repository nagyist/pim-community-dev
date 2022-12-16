<?php

declare(strict_types=1);

namespace Akeneo\Pim\Automation\IdentifierGenerator\Domain\Model\Condition;

use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Model\ProductProjection;
use Webmozart\Assert\Assert;

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @phpstan-type FamilyOperator 'IN'|'NOT IN'|'EMPTY'|'NOT EMPTY'
 * @phpstan-type FamilyNormalized array{type: string, operator: FamilyOperator, value?: string[]}
 */
final class Family implements ConditionInterface
{
    /**
     * @param FamilyOperator $operator
     * @param array|null $value
     */
    private function __construct(
        private readonly string $operator,
        private readonly ?array $value = null,
    ) {
    }

    public static function type(): string
    {
        return 'family';
    }

    /**
     * @param array<string, mixed> $normalizedProperty
     */
    public static function fromNormalized(array $normalizedProperty): self
    {
        Assert::eq($normalizedProperty['type'], self::type());
        Assert::keyExists($normalizedProperty, 'operator');
        Assert::string($normalizedProperty['operator']);
        if (\in_array($normalizedProperty['operator'], ['IN', 'NOT IN'])) {
            Assert::keyExists($normalizedProperty, 'value');
            Assert::allStringNotEmpty($normalizedProperty['value']);
            Assert::minCount($normalizedProperty['value'], 1);

            return new self($normalizedProperty['operator'], $normalizedProperty['value']);
        }

        Assert::keyNotExists($normalizedProperty, 'value');

        return new self($normalizedProperty['operator']);
    }

    /**
     * @return FamilyNormalized
     */
    public function normalize(): array
    {
        $result = [
            'type' => self::type(),
            'operator' => $this->operator,
        ];
        if (null !== $this->value) {
            $result['value'] = $this->value;
        }

        return $result;
    }

    public function match(ProductProjection $productProjection): bool
    {
        return match ($this->operator) {
            'IN' => \in_array($productProjection->familyCode(), $this->value),
            'NOT IN' => null !== $productProjection->familyCode() &&
                !\in_array($productProjection->familyCode(), $this->value),
            'EMPTY' => null === $productProjection->familyCode(),
            default => null !== $productProjection->familyCode(),
        };
    }
}