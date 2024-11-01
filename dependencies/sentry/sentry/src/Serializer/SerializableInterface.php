<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Serializer;

/**
 * This interface can be used to customize how an object is serialized in the
 * payload of an event.
 */
interface SerializableInterface
{
    /**
     * Returns an array representation of the object for Sentry.
     *
     * @return mixed[]|null
     */
    public function toSentry(): ?array;
}
