<?php

namespace TOT\Integrations\Sentry\SDK;

class Initiation
{
	private $dsn;

	public static function inst($dsn)
	{
		return new self($dsn);
	}

	private function __construct($dsn)
	{
		$this->dsn = $dsn;
	}

	public function doInit()
	{
		\TOT\Dependencies\Sentry\init([
			'dsn' => $this->dsn,
            'release' => 'wordpress-app@' . tot_plugin_get_version(),
            'environment' => defined('TOT_ENV') ? TOT_ENV : 'production',
			'default_integrations' => false,
			// Specify a fixed sample rate
			'traces_sample_rate' => 1.0,
			// Set a sampling rate for profiling - this is relative to traces_sample_rate
			'profiles_sample_rate' => 1.0,
		]);
	}
}