<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Util;

use TOT\Dependencies\Sentry\Client;
use TOT\Dependencies\Sentry\Dsn;

/**
 * @internal
 */
final class Http
{
    /**
     * @return string[]
     */
    public static function getRequestHeaders(Dsn $dsn, string $sdkIdentifier, string $sdkVersion): array
    {
        $authHeader = [
            'sentry_version=' . Client::PROTOCOL_VERSION,
            'sentry_client=' . $sdkIdentifier . '/' . $sdkVersion,
            'sentry_key=' . $dsn->getPublicKey(),
        ];

        return [
            'Content-Type: application/x-sentry-envelope',
            'X-Sentry-Auth: Sentry ' . implode(', ', $authHeader),
        ];
    }

    /**
     * @param string[][] $headers
     *
     * @param-out string[][] $headers
     */
    public static function parseResponseHeaders(string $headerLine, array &$headers): int
    {
        if (strpos($headerLine, ':') === false) {
            return \strlen($headerLine);
        }

        [$name, $value] = explode(':', trim($headerLine), 2);

        $name = trim($name);
        $value = trim($value);

        if (isset($headers[$name])) {
            $headers[$name][] = $value;
        } else {
            $headers[$name] = (array) $value;
        }

        return \strlen($headerLine);
    }
}
