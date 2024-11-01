<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Metrics\Types;

use TOT\Dependencies\Sentry\Metrics\MetricsUnit;

/**
 * @internal
 */
final class DistributionType extends AbstractType
{
    /**
     * @var string
     */
    public const TYPE = 'd';

    /**
     * @var array<array-key, float>
     */
    private $values;

    /**
     * @param int|float $value
     */
    public function __construct(string $key, $value, MetricsUnit $unit, array $tags, int $timestamp)
    {
        parent::__construct($key, $unit, $tags, $timestamp);

        $this->add($value);
    }

    /**
     * @param int|float $value
     */
    public function add($value): void
    {
        $this->values[] = (float) $value;
    }

    public function serialize(): array
    {
        return $this->values;
    }

    public function getType(): string
    {
        return self::TYPE;
    }
}
