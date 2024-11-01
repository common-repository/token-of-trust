<?php
/**
 *
 * TOT API API_Product Request
 *
 * In order to react in real time primarily for excise tax calculations,
 * Token of Trust needs to have product information on hand.
 * To help facilitate integrations of that product information
 * we’ve developed an API to allow product master data source systems
 * to push that product information to Token of Trust so that transactions can be handled quickly.
 * We currently only support vape products but others may be supported soon.
 *
 * References
 *   - TOT API docs
 * 		@TODO
 */
namespace TOT;

class API_Product extends API_Request
{
	public $endpoint_url;
	public $request_details;

    /**
     * Send requests for the product and its variations
     * 
     * @param \WC_Product $product
     * @return void
     */
    public static function send_get_requests($product)
    {
        $api_data = ['sku' => $product->get_sku()];
        $requests = self::generate_requests_array_from_api_data($product, $api_data, 'GET');
        $responses = self::send_multiple_requests($requests);
        !empty($responses) && tot_debugger::inst()->log('API_Product - responses',
            is_array($responses) ? array_map(function ($res) { return $res->body; },$responses) : '');

        return self::parse_fetched_responses($responses);
    }

    /**
     * Send requests for the product and its variations
     * 
     * @param $product
     * @param $api_data
     * @return void
     */
    public static function send_update_requests($product, $api_data)
    {
        $requests = self::generate_requests_array_from_api_data($product, $api_data);
        $responses = self::send_multiple_requests($requests);
        !empty($responses) && tot_debugger::inst()->log('API_Product - responses',
            is_array($responses) ? array_map(function ($res) { return $res->body; },$responses) : '');
    }

    /**
     * Since each variation might have a different SKU then we will make multiple requests for each variation
     * @param \WC_Product $product
     * @param array $api_data
     * @return array[]
     */
    private static function generate_requests_array_from_api_data($product, $api_data, $method = "POST")
    {
        $requests = [];
        $api_product = new API_Product(['product' => $api_data], $method);
        
        // check sku exist for parent product
        isset($api_data['sku']) && $api_data['sku']
        && $requests[] = $api_product->get_array_for_request_multiple();

        $variation_ids = $product instanceof \WC_Product_Variable
            ? $product->get_children()
            : [];

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation->get_sku() == $api_data['sku'] || empty($variation->get_sku())) continue;

            $api_data['sku'] = $variation->get_sku();
            $api_product = new API_Product(['product' => $api_data], $method);
            $requests[] = $api_product->get_array_for_request_multiple();
        }

        return $requests;
    }

    private static function send_multiple_requests($requests)
    {
        try {
            $responses = \WpOrg\Requests\Requests::request_multiple($requests);
        } catch (\Exception $e) {
            $responses = [];
            tot_debugger::inst()->log('API_Product - Got exception sending the API_Product requests',
                $e->getMessage());
        }

        return $responses;
    }

    private static function parse_fetched_responses($responses)
    {
        $tot_product = [];
        foreach ($responses as $response) {
            // get the values from first success request
            if ($response->status_code == 200) {
                $tot_product = self::parse_fetched_response($response);
                break;
            }
        }
        
        return $tot_product;
    }

    private static function parse_fetched_response($response)
    {
        try {
            $decoded = $response->decode_body();
            $tot_product = $decoded['content']['product'] ?? [];
        } catch (\Exception $e) {
            $tot_product = [];
            tot_debugger::inst()->log('API_Product - Got exception parsing the API_Product responses',
                $e->getMessage());
        }

        return $tot_product;
    }

	public function __construct($data = array(), $method = 'POST', $headers = array())
	{
        $endpoint = 'api/product';

        // adding new products to TOT
        if ($method == 'POST') {
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            );
        } else {
            // Fetching from TOT
            $headers = [];
            $endpoint .= isset($data['product']['sku'])
                ? '/'.$data['product']['sku']
                : '';
        }

		parent::__construct($endpoint, $data, $method, $headers);
	}

	public function set_details( $data = array(), $method = 'POST', $headers = array() ) {
        if ($method == 'POST') {
            $this->set_json_encoded_details($data, $method, $headers);
        } else {
            parent::set_details($data, $method, $headers);
        }
        
	}
    
    private function set_json_encoded_details($data = array(), $method = 'POST', $headers = array())
    {
        $this->data = $data;
        $body = array_merge($data, array(
            'totApiKey' => $this->public_key,
            'totSecretKey' => $this->secret_key,
            'appDomain' => $this->app_domain
        ));

        $body['forceAllowPartialUpdate'] = true;

        $this->request_details = array(
            'method' => $method,
            'headers' => array_merge(array(
                'charset' => 'utf-8',
                'Accept-Language' => 'en-US,en;q=0.9'
            ), $headers),
            'body' => json_encode($body),
            'sslverify' => tot_ssl_verify(),
            'timeout' => $this->timeout
        );
    }
}