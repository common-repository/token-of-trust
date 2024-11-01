<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Serializer\EnvelopItems;

use TOT\Dependencies\Sentry\Event;
use TOT\Dependencies\Sentry\Profiling\Profile;
use TOT\Dependencies\Sentry\Util\JSON;

/**
 * @internal
 */
class ProfileItem implements EnvelopeItemInterface
{
    public static function toEnvelopeItem(Event $event): string
    {
        $header = [
            'type' => 'profile',
            'content_type' => 'application/json',
        ];

        $profile = $event->getSdkMetadata('profile');
        if (!$profile instanceof Profile) {
            return '';
        }

        $payload = $profile->getFormattedData($event);
        if ($payload === null) {
            return '';
        }

        return sprintf("%s\n%s", JSON::encode($header), JSON::encode($payload));
    }
}
