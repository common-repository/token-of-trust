<?php

namespace TOT\Integrations\Sentry\SDK;

use TOT\Dependencies\Sentry as SentrySDK;
use TOT\Dependencies\Sentry\State\Scope;

class Capture
{
	private static $inst = null;

	/**
	 * Singleton pattern
	 * @return self
	 */
	public static function inst()
	{
		if (is_null(self::$inst)) {
			self::$inst = new self();
		}

		return self::$inst;
	}

	private function __construct()
	{
	}

	public function info($title, $context = [])
	{
		$this->send($title, $context, SentrySDK\Severity::info());
	}

	public function warning($title, $context = [])
	{
		$this->send($title, $context, SentrySDK\Severity::warning());
	}

	public function error($title, $context = [])
	{
		$this->send($title, $context, SentrySDK\Severity::error());
	}

	private function send($title, $context, SentrySDK\Severity $type)
	{
		try {
            $this->doSend($title, $context, $type);
        } catch (\Throwable $throwable) {
            \TOT\tot_debugger::inst()->log("Failed sending to sentry due to: ", $throwable->getMessage(), 'error');
        }
	}

    private function doSend($title, $context, SentrySDK\Severity $type)
    {
        SentrySDK\withScope(function (Scope $scope) use ($title, $context, $type): void {
            $context && $this->setContextToScope($context, $scope);
            $this->setTagsToScope($scope);
            SentrySDK\captureMessage($title, $type);
        });
    }

	private function setContextToScope($context, Scope $scope)
	{
		// make it array
		if (is_object($context)) {
			$context = json_decode(json_encode($context), true);
		} else if (is_string($context)) {
			$context = ['context' => $context];
		}

		$scope->setContext('context', $context);
	}

	private function setTagsToScope(Scope $scope)
	{
		$scope->setTag('appDomain', tot_get_setting_prod_domain());
	}
}