<?php
/**
 * Handles interactions with the Softone API.
 */
class Softone_API {

    private $endpoint = 'https://hellenictooloe.oncloud.gr/s1services/';
    private $username;
    private $password;
    private $client_id;
    private $session;

    public function __construct() {
        $this->username = get_option('softone_api_username');
        $this->password = get_option('softone_api_password');
        $this->client_id = get_option('softone_client_id');
        $this->session = get_option('softone_api_session');

        // Perform login and authentication
        $this->login_and_authenticate();
    }

    /**
     * Logs in to the Softone API and authenticates.
     */
    private function login_and_authenticate() {
        // Login
        $login_response = wp_remote_post($this->endpoint, [
            'body' => wp_json_encode([
                'service' => 'login',
                'username' => sanitize_text_field($this->username),
                'password' => sanitize_text_field($this->password),
                'appId' => 1000
            ]),
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if (is_wp_error($login_response)) {
            softone_log('Login', 'Login request failed: ' . $login_response->get_error_message());
            return false;
        }

        $login_body = wp_remote_retrieve_body($login_response);
        if (!$login_body) {
            softone_log('Login', 'Login failed: Empty response body');
            return false;
        }

        // Log the raw response body for debugging
         // softone_log('Login', 'Raw response body: ' . $login_body);

        // Ensure the response is properly encoded in UTF-8
        $login_body = mb_convert_encoding($login_body, 'UTF-8', 'UTF-8');

        $login_data = json_decode($login_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            softone_log('Login', 'Login failed: Invalid JSON response - ' . json_last_error_msg());
            return false;
        }

        if (isset($login_data['success']) && $login_data['success']) {
            $this->client_id = $login_data['clientID'];
            update_option('softone_client_id', $this->client_id);
            //softone_log('Login', 'Login successful');
        } else {
            softone_log('Login', 'Login failed: ' . json_encode($login_data));
            return false;
        }

        // Authenticate
        $auth_response = wp_remote_post($this->endpoint, [
            'body' => wp_json_encode([
                'service' => 'authenticate',
                'clientID' => sanitize_text_field($this->client_id),
                'company' => 1001,
                'branch' => 1000,
                'module' => 0,
                'refid' => 266
            ]),
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if (is_wp_error($auth_response)) {
            softone_log('Authenticate', 'Authenticate request failed: ' . $auth_response->get_error_message());
            return false;
        }

        $auth_body = wp_remote_retrieve_body($auth_response);
        if (!$auth_body) {
            softone_log('Authenticate', 'Authenticate failed: Empty response body');
            return false;
        }

        // Log the raw response body for debugging
         // softone_log('Authenticate', 'Raw response body: ' . $auth_body);

        // Ensure the response is properly encoded in UTF-8
        $auth_body = mb_convert_encoding($auth_body, 'UTF-8', 'UTF-8');

        $auth_data = json_decode($auth_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            softone_log('Authenticate', 'Authenticate failed: Invalid JSON response - ' . json_last_error_msg());
            return false;
        }

        if (isset($auth_data['success']) && $auth_data['success']) {
            $this->session = $auth_data['clientID'];
            update_option('softone_api_session', $this->session);
            //softone_log('Authenticate', 'Authenticate successful');
            return true;
        } else {
            softone_log('Authenticate', 'Authenticate failed: ' . json_encode($auth_data));
            return false;
        }
    }

    /**
 * Makes a request to the Softone API.
 *
 * @param string $service The service to call.
 * @param array $data The data to send.
 * @return mixed The response from the API.
 */
	private function request($service, $data) {
		
		$data['service'] = sanitize_text_field($service);
		$data['session'] = $this->session;
		// if($data['service']=='getLastUpdatedItems'){
			// $endpoint="https://hellenictooloe.oncloud.gr/s1services/js/HellenicTool.WServices/getLastUpdatedItems";
			// $response = wp_remote_post($endpoint, [
				// 'body' => wp_json_encode($data),
				// 'headers' => ['Content-Type' => 'application/json','Content-Encoding' => 'gzip','charset' => 'gzip'
				// ]
			// ]);
		// }
		// else{
			$response = wp_remote_post($this->endpoint.$data['service'], [
				'body' => json_encode($data),
				'headers' => ['Content-Type' => 'application/json','Content-Encoding' => 'gzip','charset' => 'gzip']
			]);
		// }
		if (is_wp_error($response)) {
			softone_log($service, 'API request failed: ' . iconv("Windows-1253", "UTF-8", $response->get_error_message()));
			softone_log($endpoint, 'API request failed: ' . iconv("Windows-1253", "UTF-8", $response->get_error_message()));
			return false;
		}

		$body = wp_remote_retrieve_body($response);
		if (!$body) {
			softone_log($service, 'API request failed: Empty response body');
			return false;
		}
		else{
			$data['success']='true';
		}

		// Ensure the response is properly encoded in UTF-8
		$body = mb_convert_encoding($body, 'UTF-8', 'ISO-8859-7');

		$data['body'] = json_decode($body, true);
		$data['counter']=count($data['body']);
		if (json_last_error() !== JSON_ERROR_NONE) {
			softone_log($service, 'API request failed: Invalid JSON response - ' . json_last_error_msg());
			return false;
		}

		if (!isset($data['success']) || !$data['success']) {
			softone_log($service, 'API request failed: ' . mb_convert_encoding($data['error'], 'UTF-8', 'UTF-8'));		
			return false;
		}
		return $data;
	}


    /**
     * Fetches customers from the Softone API.
     *
     * @return array|false The customers data or false on failure.
     */
    public function get_customers() {
        return $this->request('SqlData', [
            'clientid' => $this->session,
            'appId' => '1000',
            'SqlName' => 'getCustomers'
        ]);
    }

    /**
     * Fetches products from the Softone API.
     *
     * @return array|false The products data or false on failure.
     */
    public function get_products() {
        return $this->request('SqlData', [
            'clientid' => $this->session,
            'appId' => '1000',
            'SqlName' => 'getItems'
        ]);
    }

    /**
	createSale: Web Service δημιουργίας παραγγελίας
				URL: https://hellenictooloe.oncloud.gr/s1services/js/HellenicTool.WServices/createSale
				Request
				{
				"clientID": "9J…11",
				"customerID": "127096",
				"sales_series": 7001,
				CONQUEST 11
				"paymentID": 1000,
				"shipmentID": 1,
				"shipkindID": 1000,
				"customer_remarks": "",
				"shipping_address": "Σύνταγμα",
				"shipping_zip": "11111",
				"shipping_district": "Αθήνα",
				"shipping_city": "Αθήνα",
				"web_id": 660,
				"item_lines": [
				{
				"SKU": "12",
				"mtrl": "95062",
				"quantity": 1,
				"price": "10.00",
				"discount1": 0
				}
				],
				"expn": [{
				"expn_ID": 104,
				"expn_net_val": 5
				}]
				}
				Response
				{
				"success": true,
				"ID": 86428
				}
     * Creates an order in the Softone API.
     *
     * @param WC_Order $order The WooCommerce order.
     * @return bool True on success, false on failure.
     */
    public function create_order($order, $soft1Customer) {
        $items = [];
		$fees = [];
			
		$order_id = $order->get_id();
		$customer_id=$soft1Customer['cust_ID'];
		$soft1OrderKey=$this->findOrderKey($order_id);
		/*
			'item_lines'=> [
								[
									'SKU'=> '12',
									'mtrl'=> '95062',
									'quantity'=> 1,
									'price'=> '10.00',
									'discount1'=> 0
								]
							],
		*/
		foreach ($order->get_items() as $item_id => $item) {
			$product = $item->get_product();
			$items[] = [
				'SKU'=>  $product->get_sku(),
				'quantity' => $item->get_quantity(),
				'price' => $product->get_price(),
				'discount1' => 0
			];
		}

		// Capture payment method
		// $payment_method = $order->get_payment_method(); // e.g., 'cod', 'paypal', 'bacs' etc.
		// softone_log('payment_method', $payment_method);
		
		$payment_method_title = $order->get_payment_method_title(); // e.g., 'Cash on Delivery', 'PayPal'

		// Include payment method in comments
		// $order_comments = $order->get_customer_note();
		$order_comments = "#".$order->get_id()."# \nPayment Method: " . $payment_method_title;
		$order_remarks =  sanitize_text_field($order->get_customer_note());
		
		//Add shipping expences if any
		$shippingExpences = ($order->get_shipping_total());
		if($shippingExpences>0){
			$shippingExpences = ($shippingExpences/1.24);
			// softone_log('order_shipping_total', $order->get_shipping_total());
			$fees[]=[
					'EXPN' => 104,
					'EXPVAL' => $shippingExpences
				];
		}
		
		//Add payment fees if any
		$paymentMethodFees = ($order->get_total_fees());
		if($paymentMethodFees>0){
			// softone_log('order_payment_method_fees', $order->get_total_fees());
			$paymentMethodFees = ($paymentMethodFees/1.24);
			$fees[]=[
					'expn_id' => 104,
					'expn_net_val' => 5
				];
		}
		
		//get district only Greek
		$district=$this->getDistrictName($order->get_shipping_state());
		
		$order_data = [
			'SERIES' => '7023', // This should be defined based on your Softone settings
					'TRDR_CUSTOMER_CODE' => $soft1CustomerCode, // Map this appropriately
					'TRNDATE' => date("Y-m-d H:i:s"), //'2025-04-14 08:50:19',//sgmdate('Y-m-d H:i:s', strtotime($order->get_date_created())),
					'PAYMENT' => '1000',
					'SHIPMENT' => '1000',
					'COMMENTS' => $order_comments, // Add payment method to the comments
					'REMARKS' => $order_remarks, // Client order remarks
					'NUM01' => $order_id
				,
			'EXPANAL' => $fees,
			'MTRDOC' => [
			   [
					'SHIPPINGADDR' => $order->get_billing_address_1(),
					'SHPZIP' => $order->get_billing_postcode(),
					'SHPCITY' => $order->get_billing_city(),
					'SHPDISTRICT' => $district,
			   ]
			], 
			
			'ITELINES' => $items
		];
		$shipping_address = $order->get_shipping_address_1();
		$shipping_postcode = $order->get_shipping_postcode();
		$shipping_city = $order->get_shipping_city();
		$response = $this->request('createSale', [
			'clientID' => $this->session,
			'appID' => '1000',
			'customerID'=> $customer_id,
			'sales_series'=> 7001,
			'paymentID'=> 1001,
			'shipmentID'=> 1,
			'shipkindID'=> 1000,
			'customer_remarks'=> $order_remarks,
			'shipping_address'=> $shipping_address,
			'shipping_zip'=> $shipping_postcode,
			'shipping_district'=> $district,
			'shipping_city'=> $shipping_city,
			'web_id'=> $order_id,
			'item_lines'=> [
								[
									'SKU'=> '12',
									'mtrl'=> '95062',
									'quantity'=> 1,
									'price'=> '10.00',
									'discount1'=> 0
								]
							],
			'expn'=> [
						[
						'expn_ID'=> 104,
						'expn_net_val'=> 5
						]
					 ]
			
			
			
			
			
		
		]);

		if ($response) {
			if($soft1OrderKey!=''){
				//softone_log('order_updated', 'Order updated to Soft1 successfully: ' . '#'.$order_id.'#');
				return true;
			}
			else {
			//	softone_log('create_order', 'Order sent to Soft1 successfully: ' . '#'.$order_id.'#');
				return true;
			}
			
		} else {
			softone_log('create_order', 'Failed to send order to Soft1: ' . '#'.$order_id.'#');
			return false;
		}
    }
	
	
	public function getDistrictName($districtId){
		$districts=array(
			"I"=>"Αττική",
			"A"=>"Ανατολική Μακεδονία και Θράκη",
			"B"=>"Κεντρική Μακεδονία",
			"C"=>"Δυτική Μακεδονία",
			"D"=>"Ήπειρος",
			"E"=>"Θεσσαλία",
			"F"=>"Ιόνια νησιά",
			"G"=>"Δυτική Ελλάδα",
			"H"=>"Στερεά Ελλάδα",
			"J"=>"Πελοπόννησος",
			"K"=>"Βόρειο Αιγαίο",
			"L"=>"Νότιο Αιγαίο",
			"M"=>"Κρήτη");
		//$district = $order->get_billing_state();
		
		// Get the country name for "GR" country code *(Greece)*:
		

		// Get the state name for "D" state code *(Epirus)*:
		$state_name = $districts[$districtId];
		return $state_name;
	}

	public function findCustomerPhone($customer_phone){
		$response = $this->request('getBrowserInfo', [
			'clientID' => $this->session,
			'appID' => '1000',
			'object' => 'CUSTOMER',
			'list'=>'',
			'version'=>1,
			'limit'=> 1,
			'filters'=>'CUSTOMER.PHONE01=' . $customer_phone,
		]);
		if(isset($response['totalcount']) && $response['totalcount']>0){
			$soft1CustomerCode=$response['rows'][0][2];
			return $soft1CustomerCode;
		}
		else {
			return false;
		}
		
	}
	
	public function fetchCustomer($data){
		/*		https://hellenictooloe.oncloud.gr/s1services/js/HellenicTool.WServices/fetchCustomer
			Request
			{
			"clientID": "9J8…L11",
			"srchAfm": "014827616",
			"email": "atraposbio@gmail.com",
			"phone": "6973270292",
			"trdr": "127096"
			}
			
			Response
			[
			{
			"cust_ID": "127096",
			"cust_Code": "03500900000000000001",
			"cust_descr": "ΧΑΝ ΜΑΡΙΑ",
			"custAfm": "014827616",
			"cust_phone": "6973270292",
			"cust_Email": "atraposbio@gmail.com",
			"company_branch": "1000"
			}
			]
		*/
		$response = $this->request('js/HellenicTool.WServices/fetchCustomer', [
			'clientID' => $this->session,
			'email' =>$data['email'],
			'limit' =>1
		]);
		return $response;
	}
	public function lastUpdateDate ($object){
		$date = new DateTime('now', new DateTimeZone('UTC'));
		$dateString1 = "2026-01-01T00:00:00Z";
		$dateString2 = $date->format('Y-m-d\TH:i:s\Z'); // Παράδειγμα δεύτερης ημερομηνίας
		$file = '../wp-content/plugins/hellenicToolsBridge/'.$object.'_sync_log.txt';

		// 1. Έλεγχος και ανάγνωση της τελευταίας ημερομηνίας εκτέλεσης
		if (file_exists($file) && filesize($file) > 0) {
			// Διαβάζουμε όλο το αρχείο σε έναν πίνακα, όπου κάθε στοιχείο είναι μια γραμμή
			$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
			// Παίρνουμε την τελευταία γραμμή του αρχείου
			$lastLine = end($lines);
			$date = new DateTime($lastLine, new DateTimeZone('UTC'));
			$dateString1 = $date->format('Y-m-d\TH:i:s\Z');
			// echo "Η τελευταία εκτέλεση έγινε στις: " . $lastLine . PHP_EOL;
		} 
		else {
			// echo "Αυτή είναι η πρώτη φορά που εκτελείται η εργασία!" . PHP_EOL;
			file_put_contents($file, $dateString1 . PHP_EOL, FILE_APPEND | LOCK_EX);
			// $currentDateString = $dateString2; // π.χ. 2026-09-25T12:50:00Z

			// Προσθήκη της νέας ημερομηνίας στο αρχείο
			file_put_contents($file, $dateString2 . PHP_EOL, FILE_APPEND | LOCK_EX);
			return $dateString1;
		}
		
		file_put_contents($file, $dateString2 . PHP_EOL, FILE_APPEND | LOCK_EX);
		return $dateString1;
	}
	
	public function getLastUpdatedItems($fromDate){
		$response = $this->request('js/HellenicTool.WServices/getLastUpdatedItems', [
			'clientID' => $this->session,
			'appID' => '1000',
			'updateItemDate'=> $fromDate,
		]);
		return $response;		
	}
	
	public function updateInsertItems($lastUpdatedItems){
		$products=array();
		$update=0;
		$insert=0;
		$skipped=0;
		
		// $products[]=1;
		// print_r($lastUpdatedItems);
		foreach ($lastUpdatedItems as $updatedItem){
			
            //we split item code to get the item sku,the first letter before the dash is to describe the manufacturer in softone
			$sku=explode('-',$updatedItem['item_code']);
			// $sku_id=explode('-',$updatedItem['item_code'])[1];
			if ($sku[0]<>'F') {
				$skipped++;
				continue;
			}
			// if (isset($updatedItem['dim1_erp_id'])) { continue; $sku .= "-".$updatedItem['dim1_erp_id'];}
			// if (isset($updatedItem['dim2_erp_id'])) { continue;  $sku .= "-".$updatedItem['dim2_erp_id'];}
			
			$product_id = wc_get_product_id_by_sku( $sku[1] );
			if ( $product_id ) {
				$products['items'][$sku[1]] = $product_id ;
				$update++;
				//wc_get_product( $product_id );
			}
			else {
				$products['items'][$sku[1]] = 'to be inserted';
				$insert++;
			}
			//= wc_get_products( array( 'sku' => $sku ) );
		}
		// print_r($product);
		$products['update']=$update;
		$products['insert']=$insert;
		$products['skipped']=$skipped;
		return $products;
	}
//	https://hellenictooloe.oncloud.gr/s1services/js/HellenicTool.WServices/getLastUpdatedItems
	
	
	
	public function createCustomer($order){
		$woocommerce_customer_id="woocommerce_user_id:".$order->get_customer_id();
		
		$district=$this->getDistrictName($order->get_billing_state());
		
		$customer_data=[
			'CUSTOMER' => [
				[
					'CODE'=> '001-*', 
					'NAME'=> $order->get_billing_first_name() .' - '. $order->get_billing_last_name() .' - ' . $order->get_billing_company(),
					'AFM' => '',  
					'IRSDATA' => '',
					'EMAIL' => $order->get_billing_email(),
					'WEBPAGE' => '',
					'PHONE01' => $order->get_billing_phone(),  
					'PHONE02' => '',  
					'ADDRESS' => $order->get_billing_address_1(),
					'CITY' => $order->get_billing_city(),
					'ZIP' => $order->get_billing_postcode(),
					'DISTRICT' => $district,
				],
			],
			'CUSEXTRA' => [
					[
						'VARCHAR01' => $woocommerce_customer_id,
					]
				],
		];
		 $response = $this->request('setData', [
            'clientID' => $this->session,
            'appID' => '1000',
            'object' => 'CUSTOMER',
            'data' => $customer_data
        ]);
		// softone_log('create_customer', $respose['success']);
		// softone_log('state', $state_name);
		return $response;
	}
	
	
	public function update_customer($data){
		/*
			URL: https://hellenictooloe.oncloud.gr/s1services/js/HellenicTool.WServices/updateCustomer
			Πελάτης Εσωτερικού
			Request
			{
			"clientID": "9J….11",
			"customer_ID": "Test Customer WS",
			"customer_name": "Test Customer WS",
			"customer_code": "Test Customer code",
			"customer_category": 3099,
			"customer_address": "Athens",
			"customer_zip": "10000",
			"customer_city": "Athens",
			"customer_district": "district test",
			"customer_phone": "customer_phone",
			"customer_email": "test@test.gr",
			"customer_irsdata": "1101",
			"customer_webID": "101",
			"AFM": "777777777",
			"customer_occupation": "online shop",
			"customer_country": 1000
			}
			Response
			{
			"customer_ID": 129779,
			"success": true
			}
		*/
		
		$data['clientID'] = $this->session;
        $data['appID'] = '1000';
		$response = $this->request('updateCustomer', $data);
		if(isset($response['customer_ID']) && $response['customer_ID']){
			// softone_log('New customer created, soft1 ID: ',$response['customer_ID']);
			return($response);
		}
		else {
			return($response);
		}
	}
	public function create_customer($data){
		/*
			URL: https://hellenictooloe.oncloud.gr/s1services/js/HellenicTool.WServices/createCustomer
			Πελάτης Εσωτερικού
			Request
			{
			"clientID": "9J….11",
			"customer_ID": "Test Customer WS",
			"customer_name": "Test Customer WS",
			"customer_code": "*", //star create new code
			"customer_category": 3099,
			"customer_address": "Athens",
			"customer_zip": "10000",
			"customer_city": "Athens",
			"customer_district": "district test",
			"customer_phone": "customer_phone",
			"customer_email": "test@test.gr",
			"customer_irsdata": "1101",
			"customer_webID": "101",
			"AFM": "777777777",
			"customer_occupation": "online shop",
			"customer_country": 1000
			}
			Response
			{
			"customer_ID": 129779,
			"success": true
			}
		*/
		
		$data['clientID'] = $this->session;
        $data['appID'] = '1000';
		$response = $this->request('createCustomer', $data);
		if(isset($response['customer_ID']) && $response['customer_ID']){
			// softone_log('New customer created, soft1 ID: ',$response['customer_ID']);
			return($response);
		}
		else {
			return($response);
			//'success'=>false,'customer_ID'=>$response['customer_ID']);
		}
	}
	
	
	public function findProductKey($product) {
		$sku='';
		$sku=$product->get_sku();
		
		//CHECK IF ITEM EXIST
		$response = $this->request('getBrowserInfo', [
			'clientID' => $this->session,
			'appID' => '1000',
			'object' => 'ITEM',
			'list'=>'',
			'version'=>1,
			'limit'=> 1,
			'filters'=>'ITEM.CODE=' . $sku,
		]);
		
		$soft1ItemKey='';
		
		if(isset($response['rows']) && $response['rows']){
			$soft1ItemKey=explode(';',$response['rows'][0][0])[1];
			// softone_log('Item found: ',$product->get_sku().' with Soft1 Key: ' . $soft1ItemKey.' will be updated!');
			return $soft1ItemKey;
		}
		else {
			// softone_log('Item not found: ',$product->get_sku().' will be inserted in Soft1!');
			return '';
		}
	}
		public function findSoft1Product($sku) {
		// $sku='';
		// $sku=$product->get_sku();
		
		//CHECK IF ITEM EXIST
		$response = $this->request('getBrowserInfo', [
			'clientID' => $this->session,
			'appID' => '1000',
			'object' => 'ITEM',
			'list'=>'',
			'version'=>1,
			'limit'=> 1,
			'filters'=>'ITEM.CODE=*' . $sku,
		]);
		
		// $soft1ItemKey=$response;
	    // return $soft1ItemKey;
		if(isset($response['body']['rows'][0]) && $response['body']['rows'][0]){
			$soft1ItemKey=$response['body']['rows'];
			// softone_log('Item found: ',$product->get_sku().' with Soft1 Key: ' . $soft1ItemKey.' will be updated!');
			return $soft1ItemKey;
		}
		else {
			// softone_log('Item not found: ',$product->get_sku().' will be inserted in Soft1!');
			return 'not found';
		}
	}
	public function findOrderKey($order_id) {
		
		//CHECK IF Order EXIST
		$dateFrom = date("Y/d/m", strtotime("-3 months"));
		$dateTo = date("Y/d/m");
		//softone_log('Search Order ',$dateFrom.' - ' . $dateTo.' with order id:'.$order_id);
		$response = $this->request('getBrowserInfo', [
			'clientID' => $this->session,
			'appID' => '1000',
			'object' => 'SALDOC',
			'list'=>'',
			'version'=>1,
			'limit'=> 1,
			'filters'=>"FINDOC.TRNDATE='".$dateFrom."&FINDOC.TRNDATE_TO='".$dateTo."'&num01=" . $order_id,
		]);
		
		$soft1OrderKey='';
		
		if(isset($response['rows']) && $response['rows']){
			$soft1OrderKey=explode(';',$response['rows'][0][0])[1];
		//	softone_log('Order found: ',$order_id.' with Soft1 Key: ' . $soft1OrderKey.' will be updated!');
			return $soft1OrderKey;
		}
		else {
			// softone_log('Item not found: ',$product->get_sku().' will be inserted in Soft1!');
			return '';
		}
	}
	
	public function upsertVariables($product){
		$variation_ids = $product->get_children();
		
		foreach ( $variation_ids as $variation_id ) {
			
			$variation = wc_get_product( $variation_id );
			$variationName = implode(" / ", $variation->get_variation_attributes());
			//softone_log('Item :'.$variation->get_sku().' Variation id:',$variation_id);
			if($variation->get_sku()){
				
				$soft1ItemKey=$this->findProductKey($variation);
				$variation_data = [
					'ITEM' => [
						[
							'CODE'=> $variation->get_sku(), 
							'NAME'=> $product->get_name().' - '.$variationName,
							'VAT' => '1410',  
							'MTRUNIT1' => 101,
							'MTRUNIT2' => 101,
							'MTRUNIT3' => 101,
							'MTRUNIT4' => 101,  
							'MTRACN' => 101,  
							'PRICER' => $variation->get_regular_price(),
							'GWEIGHT' => ($variation->get_weight())?$variation->get_weight():null,
						],
					],
				];
				
				$response = $this->request('setData', [
					'clientID' => $this->session,
					'appID' => '1000',
					'object' => 'ITEM',
					'key' => ($soft1ItemKey)?$soft1ItemKey:'',
					'data' => $variation_data
				]);

				if ($response) {
					if($soft1ItemKey){
					//	softone_log('Item updated:', 'Item updated to Soft1 successfully. SKU:' .count($variation_ids).'-'. $variation->get_sku(). 'Soft1 Key:' . $soft1ItemKey);
						//return true;
					}
					else {	
					//	softone_log('Item created:', 'Item created to Soft1 successfully. SKU:' .count($variation_ids).'-'. $variation->get_sku(). 'Soft1 Key:' . $response['id']);
						//return true;
					}
				} 
				
				else {
					softone_log('Failed :', 'Failed to send Item to Soft1: ' . $variation->get_sku());
					//return false;
				}
			}
		}	
	}
	public function upsertProduct($product) {		
		
		$sku=($product->get_sku());
		
		if($sku){
			
			$soft1ItemKey=$this->findProductKey($product);
			
			$product_data = [
				'ITEM' => [
					[
						'CODE'=> $product->get_sku(), 
						'NAME'=> $product->get_name(),
						'VAT' => '1410',  
						'MTRUNIT1' => 101,
						'MTRUNIT2' => 101,
						'MTRUNIT3' => 101,
						'MTRUNIT4' => 101,  
						'MTRACN' => 101,  
						'PRICER' => $product->get_regular_price(),
						'GWEIGHT' => ($product->get_weight())?$product->get_weight():null,
					],
				],
			 
			];
			
			$response = $this->request('setData', [
				'clientID' => $this->session,
				'appID' => '1000',
				'object' => 'ITEM',
				'key' => ($soft1ItemKey)?$soft1ItemKey:'',
				'data' => $product_data
			]);

			if ($response) {
				if($soft1ItemKey){
					//softone_log('Item updated:', 'Item updated to Soft1 successfully. SKU:' . $product->get_sku(). 'Soft1 Key:' . $soft1ItemKey);
					return true;
				}
				else {	
				//	softone_log('Item created:', 'Item created to Soft1 successfully. SKU:' . $product->get_sku(). 'Soft1 Key:' . $response['id']);
					return true;
				}
			} 
			
			else {
				softone_log('Failed :', 'Failed to send Item to Soft1: ' . $product->get_sku());
				return false;
			}
			
			
		}
		else{
			//softone_log('Create item failed: SKU='.(($sku)?$sku:'null'), 'Please set SKU! On product with ID:'.$product->get_id());
			return false;
		}
    }
}
?>