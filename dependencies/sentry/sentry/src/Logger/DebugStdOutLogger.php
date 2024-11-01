<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Logger;

use TOT\Dependencies\Psr\Log\AbstractLogger;

class DebugStdOutLogger extends AbstractLogger
{
    /**
     * @param mixed   $level
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        file_put_contents('php://stdout', sprintf("sentry/sentry: [%s] %s\n", $level, (string) $message));
    }
}
