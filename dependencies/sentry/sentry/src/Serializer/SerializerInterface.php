<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Serializer;

/**
 * Basic serializer for the event data.
 */
interface SerializerInterface
{
    /**
     * Serialize an object (recursively) into something safe to be sent in an Event.
     *
     * @param mixed $value
     *
     * @return string|bool|float|int|object|mixed[]|null
     */
    public function serialize($value);
}
