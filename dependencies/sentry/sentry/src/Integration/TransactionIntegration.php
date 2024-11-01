<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Integration;

use TOT\Dependencies\Sentry\Event;
use TOT\Dependencies\Sentry\EventHint;
use TOT\Dependencies\Sentry\SentrySdk;
use TOT\Dependencies\Sentry\State\Scope;

/**
 * This integration sets the `transaction` attribute of the event to the value
 * found in the raw event payload or to the value of the `PATH_INFO` server var
 * if present.
 *
 * @author Stefano Arlandini <sarlandini@alice.it>
 */
final class TransactionIntegration implements IntegrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(static function (Event $event, EventHint $hint): Event {
            $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);

            // The client bound to the current hub, if any, could not have this
            // integration enabled. If this is the case, bail out
            if ($integration === null) {
                return $event;
            }

            if ($event->getTransaction() !== null) {
                return $event;
            }

            if (isset($hint->extra['transaction']) && \is_string($hint->extra['transaction'])) {
                $event->setTransaction($hint->extra['transaction']);
            } elseif (isset($_SERVER['PATH_INFO'])) {
                $event->setTransaction($_SERVER['PATH_INFO']);
            }

            return $event;
        });
    }
}
