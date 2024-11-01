<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Serializer\EnvelopItems;

use TOT\Dependencies\Sentry\Event;
use TOT\Dependencies\Sentry\Util\JSON;

/**
 * @internal
 */
class CheckInItem implements EnvelopeItemInterface
{
    public static function toEnvelopeItem(Event $event): string
    {
        $header = [
            'type' => (string) $event->getType(),
            'content_type' => 'application/json',
        ];

        $payload = [];

        $checkIn = $event->getCheckIn();
        if ($checkIn !== null) {
            $payload = [
                'check_in_id' => $checkIn->getId(),
                'monitor_slug' => $checkIn->getMonitorSlug(),
                'status' => (string) $checkIn->getStatus(),
                'duration' => $checkIn->getDuration(),
                'release' => $checkIn->getRelease(),
                'environment' => $checkIn->getEnvironment(),
            ];

            if ($checkIn->getMonitorConfig() !== null) {
                $payload['monitor_config'] = $checkIn->getMonitorConfig()->toArray();
            }

            if (!empty($event->getContexts()['trace'])) {
                $payload['contexts']['trace'] = $event->getContexts()['trace'];
            }
        }

        return sprintf("%s\n%s", JSON::encode($header), JSON::encode($payload));
    }
}
