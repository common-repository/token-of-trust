<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Transport;

use TOT\Dependencies\Sentry\Event;

interface TransportInterface
{
    public function send(Event $event): Result;

    public function close(?int $timeout = null): Result;
}
