<?php
namespace TOT\Integrations\WooCommerce;

use TOT\tot_debugger;

/**
 * @mixin Checkout_Enabled|Checkout_Bridge
 */
class Checkout_Hook_Handler
{
    /** @var Checkout */
    private $wrappedObj;

    public static function getInstance($checkoutObj)
    {
        return new self($checkoutObj);
    }

    private function __construct($checkoutObj)
    {
        $this->wrappedObj = $checkoutObj;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed|void. return statement is for failed add_filter
     */
    public function __call($method, $args)
    {
        $checkout = $this->wrappedObj;
        try {
            return $checkout->$method(...$args);
        } catch (\Throwable $throwable) {
            tot_debugger::inst()->log(
                "TOT_Checkout_Thrown_Error: " . $throwable->getMessage() . ' in ' . $throwable->getFile(),
                $throwable->getTraceAsString(),
                'error'
            );
            error_log($throwable);

            return $this->handle_error_for_method($method, $args);
        }
    }

    /**
     * handle error if possible
     * @see self::handle_add_awaiting_verification_to_order_statuses()
     */
    private function handle_error_for_method($method, $args)
    {
        $error_handler_function = 'handle_' . $method;
        if (method_exists($this, $error_handler_function)) {
            return $this->$error_handler_function(...$args);
        }

        return $args[0] ?? null;
    }
}