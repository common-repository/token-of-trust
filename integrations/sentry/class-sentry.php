<?php

namespace TOT\Integrations\Sentry;

use TOT\Integrations\Sentry\SDK\Capture;
use TOT\Integrations\Sentry\SDK\Initiation;
use TOT\Settings;
use TOT\tot_debugger;

/**
 * Bridge pattern to use Sentry SDK or Sentry API on the future
 *
 * @method captureInfo(string $head, string $log)
 * @method captureWarning(string $head, string $log)
 * @method captureError(string $head, string $log)
 */
class Sentry
{
	private static $inst = null;

	private $dsn;

	/**
	 * @var Initiation|null
	 */
	private $initiation = null;

	/**
	 * @var Capture|null
	 */
	private $capture = null;

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

	public static function captureRefreshedDSN()
	{
        self::killSentry();
		return self::inst()->setup()->captureInfo('DSN is refreshed');
	}

    private static function killSentry()
    {
        self::$inst = null;
    }

    /**
     * @see self::_captureInfo()
     * @see self::_captureWarning()
     * @see self::_captureError()
     * @param $name
     * @param $arguments
     * @return void
     */
    public function __call($name, $arguments)
    {
        $methodName = '_' . $name;
        try {
            $this->$methodName(...$arguments);
        } catch (\Throwable $throwable) {
            // kill sentry to break endless loop
            self::killSentry();
            tot_debugger::inst()->log('Failed with calling method: ' . $methodName, $throwable->getMessage(), 'error');
        }
    }

	private function __construct()
	{
	}

	private function isActive()
	{
        $options = get_option('tot_options');

        return tot_get_setting_prod_domain()
            && isset($options['tot_field_license_key']) && $options['tot_field_license_key']
            && (tot_keys_work('live') || tot_keys_work('test'))
            && (bool)$this->getDSN();
	}

    public function setup()
    {
        if ($this->isActive()) $this->doSetup();
        return $this;
    }

    private function doSetup()
    {
        try {
            $this->includeSDKClasses();
            $this->factorySDKObjs();
        } catch (\Throwable $throwable) {
            tot_debugger::inst()->log("Failed to setup Sentry", $throwable->getMessage(), 'error');
        }
    }

	private function getDSN()
	{
		if (is_null($this->dsn)) {
			$this->dsn = Settings::get_setting('tot_field_sentry_dsn');
		}

		return $this->dsn;
	}

	private function includeSDKClasses()
	{
		require_once __DIR__ . '/../../dependencies/autoload.php';
		require_once __DIR__ . '/sdk/class-initiation.php';
		require_once __DIR__ . '/sdk/class-capture.php';
	}

	private function factorySDKObjs()
	{
        $this->initiation = Initiation::inst($this->getDSN());
        $this->initiation->doInit();
        $this->capture = Capture::inst();
	}

    private function _captureInfo($head, $log = '')
    {
        $this->capture && $this->capture->info($head, $log);
    }

    private function _captureWarning($head, $log = '')
    {
        $this->capture && $this->capture->warning($head, $log);
    }

    private function _captureError($head, $log = '')
    {
        $this->capture && $this->capture->error($head, $log);
    }
}