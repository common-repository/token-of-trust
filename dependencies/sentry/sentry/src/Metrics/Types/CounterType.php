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
final class CounterType extends AbstractType
{
    /**
     * @var string
     */
    public const TYPE = 'c';

    /**
     * @var int|float
     */
    private $value;

    /**
     * @param int|float $value
     */
    public function __construct(string $key, $value, MetricsUnit $unit, array $tags, int $timestamp)
    {
        parent::__construct($key, $unit, $tags, $timestamp);

        $this->value = (float) $value;
    }

    /**
     * @param int|float $value
     */
    public function add($value): void
    {
        $this->value += (float) $value;
    }

    public function serialize(): array
    {
        return [$this->value];
    }

    public function getType(): string
    {
        return self::TYPE;
    }
}
