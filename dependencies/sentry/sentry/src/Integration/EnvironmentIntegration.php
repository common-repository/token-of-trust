<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Integration;

use TOT\Dependencies\Sentry\Context\OsContext;
use TOT\Dependencies\Sentry\Context\RuntimeContext;
use TOT\Dependencies\Sentry\Event;
use TOT\Dependencies\Sentry\SentrySdk;
use TOT\Dependencies\Sentry\State\Scope;
use TOT\Dependencies\Sentry\Util\PHPVersion;

/**
 * This integration fills the event data with runtime and server OS information.
 *
 * @author Stefano Arlandini <sarlandini@alice.it>
 */
final class EnvironmentIntegration implements IntegrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(static function (Event $event): Event {
            $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);

            if ($integration !== null) {
                $event->setRuntimeContext($integration->updateRuntimeContext($event->getRuntimeContext()));
                $event->setOsContext($integration->updateServerOsContext($event->getOsContext()));
            }

            return $event;
        });
    }

    private function updateRuntimeContext(?RuntimeContext $runtimeContext): RuntimeContext
    {
        if ($runtimeContext === null) {
            $runtimeContext = new RuntimeContext('php');
        }

        if ($runtimeContext->getVersion() === null) {
            $runtimeContext->setVersion(PHPVersion::parseVersion());
        }

        if ($runtimeContext->getSAPI() === null) {
            $runtimeContext->setSAPI(\PHP_SAPI);
        }

        return $runtimeContext;
    }

    private function updateServerOsContext(?OsContext $osContext): ?OsContext
    {
        if (!\function_exists('php_uname')) {
            return $osContext;
        }

        if ($osContext === null) {
            $osContext = new OsContext(php_uname('s'));
        }

        if ($osContext->getVersion() === null) {
            $osContext->setVersion(php_uname('r'));
        }

        if ($osContext->getBuild() === null) {
            $osContext->setBuild(php_uname('v'));
        }

        if ($osContext->getKernelVersion() === null) {
            $osContext->setKernelVersion(php_uname('a'));
        }

        if ($osContext->getMachineType() === null) {
            $osContext->setMachineType(php_uname('m'));
        }

        return $osContext;
    }
}
