<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Util;

use TOT\Dependencies\Sentry\Options;

trait PrefixStripper
{
    /**
     * Removes from the given file path the specified prefixes in the SDK options.
     */
    protected function stripPrefixFromFilePath(?Options $options, string $filePath): string
    {
        if ($options === null) {
            return $filePath;
        }

        foreach ($options->getPrefixes() as $prefix) {
            if (mb_substr($filePath, 0, mb_strlen($prefix)) === $prefix) {
                return mb_substr($filePath, mb_strlen($prefix));
            }
        }

        return $filePath;
    }
}
