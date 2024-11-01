<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Serializer;

use TOT\Dependencies\Sentry\Event;
use TOT\Dependencies\Sentry\EventType;
use TOT\Dependencies\Sentry\Options;
use TOT\Dependencies\Sentry\Serializer\EnvelopItems\CheckInItem;
use TOT\Dependencies\Sentry\Serializer\EnvelopItems\EventItem;
use TOT\Dependencies\Sentry\Serializer\EnvelopItems\MetricsItem;
use TOT\Dependencies\Sentry\Serializer\EnvelopItems\ProfileItem;
use TOT\Dependencies\Sentry\Serializer\EnvelopItems\TransactionItem;
use TOT\Dependencies\Sentry\Tracing\DynamicSamplingContext;
use TOT\Dependencies\Sentry\Util\JSON;

/**
 * This is a simple implementation of a serializer that takes in input an event
 * object and returns a serialized string ready to be sent off to Sentry.
 *
 * @internal
 */
final class PayloadSerializer implements PayloadSerializerInterface
{
    /**
     * @var Options The SDK client options
     */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(Event $event): string
    {
        // @see https://develop.sentry.dev/sdk/envelopes/#envelope-headers
        $envelopeHeader = [
            'event_id' => (string) $event->getId(),
            'sent_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'dsn' => (string) $this->options->getDsn(),
            'sdk' => [
                'name' => $event->getSdkIdentifier(),
                'version' => $event->getSdkVersion(),
            ],
        ];

        $dynamicSamplingContext = $event->getSdkMetadata('dynamic_sampling_context');
        if ($dynamicSamplingContext instanceof DynamicSamplingContext) {
            $entries = $dynamicSamplingContext->getEntries();

            if (!empty($entries)) {
                $envelopeHeader['trace'] = $entries;
            }
        }

        $items = '';

        switch ($event->getType()) {
            case EventType::event():
                $items = EventItem::toEnvelopeItem($event);
                break;
            case EventType::transaction():
                $transactionItem = TransactionItem::toEnvelopeItem($event);
                if ($event->getSdkMetadata('profile') !== null) {
                    $profileItem = ProfileItem::toEnvelopeItem($event);
                    if ($profileItem !== '') {
                        $items = sprintf("%s\n%s", $transactionItem, $profileItem);
                        break;
                    }
                }
                $items = $transactionItem;
                break;
            case EventType::checkIn():
                $items = CheckInItem::toEnvelopeItem($event);
                break;
            case EventType::metrics():
                $items = MetricsItem::toEnvelopeItem($event);
                break;
        }

        return sprintf("%s\n%s", JSON::encode($envelopeHeader), $items);
    }
}
