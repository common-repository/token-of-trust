<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Logger;

use TOT\Dependencies\Psr\Log\AbstractLogger;

class DebugFileLogger extends AbstractLogger
{
    /**
     * @var string
     */
    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @param mixed   $level
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        file_put_contents($this->filePath, sprintf("sentry/sentry: [%s] %s\n", $level, (string) $message), \FILE_APPEND);
    }
}
