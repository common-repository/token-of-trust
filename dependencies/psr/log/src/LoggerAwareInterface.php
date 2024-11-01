<?php
/**
 * @license MIT
 *
 * Modified by tokenoftrust on 22-January-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace TOT\Dependencies\Psr\Log;

/**
 * Describes a logger-aware instance.
 */
interface LoggerAwareInterface
{
    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void;
}
