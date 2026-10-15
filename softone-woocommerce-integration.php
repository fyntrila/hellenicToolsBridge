<?php
/**
 * Plugin Name: Softone WooCommerce Integration
 * Plugin URI: https://wordpress.org/plugins/softone-woocommerce-integration/
 * Description: Integrates WooCommerce with Softone API for customer, product, and order synchronization.
 * Version: 1.0.13
 * Author: Ninjaweb implementation
 * Author URI: https://ninjaweb.gr
 * Text Domain: softone-woocommerce-integration
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if WooCommerce is active
function softone_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce to be installed and active.');
    }
}
register_activation_hook(__FILE__, 'softone_check_woocommerce');

// Define plugin path
define('SOFTONE_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include necessary files
require_once SOFTONE_PLUGIN_PATH . 'includes/api.php';
require_once SOFTONE_PLUGIN_PATH . 'includes/logging.php';
require_once SOFTONE_PLUGIN_PATH . 'admin/settings-page.php';
require_once SOFTONE_PLUGIN_PATH . 'admin/customer-sync-page.php';
require_once SOFTONE_PLUGIN_PATH . 'admin/product-sync-page.php';
require_once SOFTONE_PLUGIN_PATH . 'admin/product-test-page.php';
require_once SOFTONE_PLUGIN_PATH . 'admin/woo2soft1_sync_products_page.php';
require_once SOFTONE_PLUGIN_PATH . 'admin/order-sync-page.php';
require_once SOFTONE_PLUGIN_PATH . 'admin/logs-page.php';
require_once SOFTONE_PLUGIN_PATH . 'admin/logs-page.php';
require 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/fyntrila/hellenicToolsBridge',
    __FILE__,
    'softone-woocommerce-integration'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

// Initialize plugin
function softone_woocommerce_integration_init() {
    // Add admin menu
    add_action('admin_menu', 'softone_admin_menu');
    // Register settings
    add_action('admin_init', 'softone_register_settings');
    // Add cron jobs
    add_action('softone_cron_sync_products', 'softone_sync_products');
	#add_action('softone_cron_sync_orders', 'softone_sync_orders');
    // Hook into WooCommerce order processed
    add_action('woocommerce_checkout_order_processed', 'softone_create_order', 10, 3);
	//add_action( 'woocommerce_new_order', 'softone_create_order', 10, 1 );
	//add_action( 'save_post_shop_order', 'softone_create_order', 10, 1 );
	#add_action( 'woocommerce_update_order', 'softone_create_order', 10, 1 );
	// Hook into post product save
//	add_action( 'save_post', 'product_post_save', 10, 3 );//we check if is a product post inside called function
	//Hook to allow only numbers on phone
//	add_action('wp_footer', 'ecommercehints_billing_phone_validation');
 //alt hook wp_after_insert_post
    // Hook into WooCommerce customer creation
 //   add_action('user_register', 'softone_send_new_customer_to_api', 10, 1);
    // Schedule cron jobs
   softone_schedule_cron_jobs();
}
/**
 * Snippet Name:	WooCommerce Only Allow Number Input For Billing Phone
 * Snippet Author:	ecommercehints.com
 */

//add_action('wp_footer', 'ecommercehints_billing_phone_validation');
function ecommercehints_billing_phone_validation() {
        if ( is_checkout() && ! is_wc_endpoint_url() ) :
    ?>
    <script type="text/javascript">
    jQuery( function($){
        $('#billing_phone').on( 'input focusout', function() {
            var p = $(this).val();
            $(this).val($(this).val().replace(/[^0-9]/g, ''));
        });
    });
    </script>
    <?php
    endif;
}
add_action('plugins_loaded', 'softone_woocommerce_integration_init');

// Schedule cron jobs
function softone_schedule_cron_jobs() {
    if (!wp_next_scheduled('softone_cron_sync_products')) {
        wp_schedule_event(time(), 'two_hours', 'softone_cron_sync_products');
    }
    if (!wp_next_scheduled('softone_cron_sync_orders')) {
        wp_schedule_event(time(), 'hourly', 'softone_cron_sync_orders');
    }
}

// Clear scheduled cron jobs on deactivation
function softone_clear_scheduled_cron_jobs() {
    wp_clear_scheduled_hook('softone_cron_sync_products');
    wp_clear_scheduled_hook('softone_cron_sync_orders');
}
register_deactivation_hook(__FILE__, 'softone_clear_scheduled_cron_jobs');

// Admin menu setup
/*add_submenu_page( string $parent_slug, 
					string $page_title, 
					string $menu_title, 
					string $capability, 
					string $menu_slug, 
					callable $callback = '', 
					int|float $position = null ): string|false
*/
function softone_admin_menu() {
    add_menu_page('Softone Integration', 'Softone', 'manage_options', 'softone-settings', 'softone_settings_page');
	add_submenu_page('softone-settings', 'Customer Sync', 'Customers', 'manage_options', 'softone-customers', 'softone_customers_page');
	add_submenu_page('softone-settings', 'Product Sync', 'Products', 'manage_options', 'softone-products','softone_products_page');
	add_submenu_page('softone-settings', 'Order Sync', 'Orders', 'manage_options', 'softone-orders', 'softone_orders_page');
	add_submenu_page('softone-settings', 'Product Test', 'ProductCheck', 'manage_options', 'softone-product-check', 'softone_product_test_page');
   //add_submenu_page('softone-settings', 'Product Sync', 'Products', 'manage_options', 'softone2woo-sync-products','softone_sync_products_page');
	add_submenu_page('softone-settings', 'Live Logging', 'Logs', 'manage_options', 'softone-logs', 'softone_logs_page');
}

// Register settings
function softone_register_settings() {
    register_setting('softone_settings_group', 'softone_api_username', 'sanitize_text_field');
    register_setting('softone_settings_group', 'softone_api_password', 'sanitize_text_field');
}

// Activation hook to set default options
function softone_activate() {
    // Set default values for the options if they don't exist
    if (get_option('softone_api_username') === false) {
        update_option('softone_api_username', 'WebUser');
    }
    if (get_option('softone_api_password') === false) {
        update_option('softone_api_password', 'hellenic2025!@#');
    }
    if (get_option('softone_client_id') === false) {
        update_option('softone_client_id', '1000');
    }
    if (get_option('softone_synced_customers') === false) {
        update_option('softone_synced_customers', []);
    }
    if (get_option('softone_synced_products') === false) {
        update_option('softone_synced_products', []);
    }
    if (get_option('softone_api_logs') === false) {
        update_option('softone_api_logs', []);
    }
    // Schedule cron jobs
    softone_schedule_cron_jobs();
}
register_activation_hook(__FILE__, 'softone_activate');

// Cleanup on deactivation
function softone_cleanup() {
    delete_option('softone_api_username');
    delete_option('softone_api_password');
    delete_option('softone_client_id');
    delete_option('softone_synced_customers');
    delete_option('softone_synced_products');
    delete_option('softone_api_logs');
    softone_clear_scheduled_cron_jobs();
}
register_deactivation_hook(__FILE__, 'softone_cleanup');

// Custom cron schedules
//add_filter('cron_schedules', 'softone_custom_cron_schedules');
function softone_custom_cron_schedules($schedules) {
    $schedules['two_hours'] = [
        'interval' => 7200,
        'display' => __('Every Two Hours')
    ];
    return $schedules;
}



// Hook into WooCommerce order creation and send order to Soft1
function softone_create_order($order_id,$data,$order) {
	$order2_id=$order->get_id();
	softone_log('hook create order','Order creation for order id:'.$order_id);
    // $order = wc_get_order($order_id);.'order:id2'. $order2_id.'data:'.json_encode($data).'order'.json_encode($order)
	
	
    if (!empty($order_id) && isset($order)) {
		
		// $isSetS1SaleID=$order->meta_exists('_S1SaleID_meta_key');
		// $s1SaleID=$order->get_meta('_S1SaleID_meta_key', true);
		
		$api = new Softone_API();
		$orders_customer_phone = $order->get_billing_phone();
		$orders_customer_email = $order->get_billing_email();
		
		//Fetch customer from soft1 using the order email.
		$response = $api->fetchCustomer($orders_customer_email	);		
		$soft1CustomerID=0;
		$soft1CustomerID=$response['body'][0]['cust_ID'];
		softone_log('order_creation','Order creation for order id:'. $order_id);
		softone_log('fetch_customer','Found customer with soft1 ID:'. $soft1CustomerID."email:".$orders_customer_email."response:".json_encode($response['body']));
		// $soft1Customer = $api->findCustomerPhone($customer_phone);		
		// $custID=$soft1Customer['cust_ID'];
		//Prepare the customer data for upsertProduct	
		
		//If customer exist in soft1 then continue to create order
		//we will update customer data just in case something chaged
		//email stays the same
		//We then continue to create the sale in soft1
		
		//customer email exists  we update his data and create order
        if($soft1CustomerID<>0){ 
			softone_log('fetch_customer','2Found customer with soft1 ID:'. $soft1CustomerID);
		    
			$order->update_meta_data( '_S1customerID_meta_key', $soft1CustomerID );
			$order->save();
			softone_log('Updated data of customer with soft1 ID:', $soft1CustomerID);
			$res = $api->update_customer($order);
			if(isset($res['success']) && $res['success']){
				// $soft1Customer=$api->findCustomerPhone($customer_phone);
				if(isset($res['customer_ID'])){
					$order->update_meta_data( '_S1customerID_meta_key', $res['customer_ID'] );
					$order->save();
					softone_log('Updated data of customer with soft1 ID:', $res['customer_ID']);
				}
				else{
					softone_log('Failed to update data of customer no soft1 ID returned:', $orders_customer_id);
				}
				
				$res_CreateSale = $api->create_sale($order); /// we create the new order
				if(isset($res_CreateSale['success']) && $res_CreateSale['success']){
					$order->update_meta_data( '_S1SaleID_meta_key', $res_CreateSale['ID'] );
					$order->save();
					softone_log('create_new_order', 'New Order with WC_ID:'.$order_id.' and Soft1_ID:'.$res_CreateSale['ID'].', created successfully.'.'response:'.json_encode($res_CreateSale));
				}
				else{
					softone_log('create_new_order', 'Failed to create new Order with WC_ID:'.$order_id.' and Soft1Customer_ID:'.$res['customer_ID'].',error.'.$res_CreateSale['msg'].'Softone ID'.$res_CreateSale['ID']);
				}
				// return $result;
			}
			else {
				softone_log('Failed to update data of customer with soft1 ID:', $orders_customer_id);
				return false;
			}
			
		}
		//customer email does not exist we create new customer and then create the order
		else{
			return;
			$res=$api->create_customer($order);
			$order->update_meta_data( '_S1customerID_meta_key', $res['customer_ID'] );
			$order->save();
			softone_log('New customer created with in soft1 with ID:', $res['customer_ID']);
			
			if(isset($res['success']) && $res['success']){
				// $soft1Customer=$api->findCustomerPhone($customer_phone);
				// softone_log('New customer added with code', $soft1Customer);
				
				$result = $api->create_sale($order);
				if(isset($result['success']) && $result['success']){
					$order->update_meta_data( '_S1SaleID_meta_key', $result['ID'] );
					$order->save();
					softone_log('create_new_order', 'New Order with WC_ID:'.$order_id.' and Soft1_ID:'.$result['ID'].', created successfully.');
				}
				else {
					softone_log('create_new_order', 'New Order with WC_ID:'.$order_id.' failed to be created in Soft1');
				}
				
			}
			else{
				$soft1Customer=false;
				softone_log('create_customer', 'failed to create customer ');
				return false;
			}
		}
    }
	else{
		softone_log('create_order', 'failed to create order in soft1, no order id provided');
		return false;
	}
}

// function custom_new_order_action( $order_id ) {
    // $order = wc_get_order( $order_id );
    // // Your custom code
// }

// Hook into Product save
function product_post_save( $post_ID, $post, $update ) {
	if ( 'product' === $post->post_type){
		$product = wc_get_product( $post_ID );
		if($product){
			$api = new Softone_API();
			//check if its variable or simple product
			if ( $product->is_type( 'variable' ) ) {
				$api->upsertVariables($product);
			}
			else {
				$api->upsertProduct($product);
			}
			//update_post_meta( $post->ID, '_sale_price', '8999988' );
			//update_post_meta( $post->ID, '_regular_price', '98768888885' );
		}
		else{
			softone_log('not send to softone ', $post_ID);
		}
		// $msg = 'Is this un update? ';
		//  $msg .= $update ? 'Yes.' : 'No.';
		//  wp_die( $msg );
	}
}
// Function to send new customers to Softone
/*
EndPoint: https://hellenictooloe.oncloud.gr/s1services/js/HellenicTool.WServices/createCustomer
Request
{
"clientID": "9J….11",
CONQUEST 7
"customer_name": "Test Customer WS",
"customer_code": "*",
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
function softone_send_new_customer_to_api($customer_id) {
    $user = get_userdata($customer_id);
    if ($user && in_array('customer', $user->roles)) {
        $api = new Softone_API();
        $customer_data = [
            'CUSTOMER' => [
                [
                    'CODE' => $user->user_login,
                    'NAME' => $user->first_name,
                    'EMAIL' => $user->user_email,
                    'ADDRESS' => get_user_meta($customer_id, 'billing_address_1', true),
                    'CITY' => get_user_meta($customer_id, 'billing_city', true),
                    'ZIP' => get_user_meta($customer_id, 'billing_postcode', true),
                    'COUNTRY' => get_user_meta($customer_id, 'billing_country', true),
                    'PHONE1' => get_user_meta($customer_id, 'billing_phone', true),
                ]
            ]
        ];
        $api->request('setData', [
            'clientID' => $api->session,
            'appID' => 1000,
            'object' => 'CUSTOMER',
            'data' => $customer_data
        ]);
        softone_log('send_new_customer', 'New customer sent to Softone: ' . $user->user_login);
    }
}


function woo2soft1_sync_products() {
	return;
}
// Sync products
function softone_sync_products() {
    if (class_exists('WooCommerce')) {
        $api = new Softone_API();
        // $products = $api->get_products();
		$fromDate=$api->lastUpdateDate('product');
		echo "Last update:".$fromDate;
        $products = $api->getLastUpdatedItems($fromDate);
		// echo "<pre>";
		// print_r($products['body']);
		// echo "</pre>";
        if ($products && isset($products['body'])) {
            foreach ($products['body'] as $product) {
				// if($product['WebActive']<>1) continue;
				// print_r($product);
                // Check if product exists by SKU
				$sku=explode('-',$product['item_code'])[1];
				// echo $sku."<br>";
                $existing_product_id = wc_get_product_id_by_sku($sku);
                if ($existing_product_id) {
                    // Update existing product
                    $product_obj = new WC_Product($existing_product_id);
					// if($product['WebActive']==0) $product_obj->set_status('draft');
                    $product_obj->set_name(sanitize_text_field($product['item_descr']));
                    $product_obj->set_price(floatval($product['price_WholeSale']));
                    $product_obj->set_regular_price(floatval($product['price_WholeSale']));
                    if($product['WebActive']==0) {
						$product_obj->set_stock_quantity(intval('0'));
					}
					else {
						$product_obj->set_stock_quantity(intval($product['rem']));
					}
                    $product_obj->set_manage_stock(true);

                    // Update categories and subcategories
                    $category_ids = array();
                    if (!empty($product['item_category'])) {
                        $category_id = get_term_by('name', sanitize_text_field($product['item_category']), 'product_cat');
                        if ($category_id) {
                            $category_ids[] = $category_id->term_id;
                        } else {
                            // Create new category if it does not exist
                            $new_category = wp_insert_term(sanitize_text_field($product['item_category']), 'product_cat');
                            if (!is_wp_error($new_category)) {
                                $category_ids[] = $new_category['term_id'];
                            }
                        }
                    }
                    if (!empty($product['SUBMECATEGORY_NAME'])) {
                        $subcategory_id = get_term_by('name', sanitize_text_field($product['SUBMECATEGORY_NAME']), 'product_cat');
                        if ($subcategory_id) {
                            $category_ids[] = $subcategory_id->term_id;
                        } else {
                            // Create new subcategory if it does not exist
                            $new_subcategory = wp_insert_term(sanitize_text_field($product['SUBMECATEGORY_NAME']), 'product_cat');
                            if (!is_wp_error($new_subcategory)) {
                                $category_ids[] = $new_subcategory['term_id'];
                            }
                        }
                    }
                    if (!empty($category_ids)) {
                        $product_obj->set_category_ids($category_ids);
                    }
					// print_r($product_obj);
                    $product_obj->save();
					$result[$sku]=$product;
					$result[$sku]['action']='update';
                } else {
					if($product['WebActive']==0) continue;
						
                    // Create new product
                    $new_product = new WC_Product();
                    $new_product->set_name(sanitize_text_field($product['item_descr']));
                    $new_product->set_sku(sanitize_text_field($sku));
                    $new_product->set_price(floatval($product['price_WholeSale']));
                    $new_product->set_regular_price(floatval($product['price_WholeSale']));
                    $new_product->set_stock_quantity(intval($product['rem']));
                    $new_product->set_manage_stock(true);

                    // Set categories and subcategories
                    $category_ids = array();
                    if (!empty($product['item_category'])) {
                        $category_id = get_term_by('name', sanitize_text_field($product['item_category']), 'product_cat');
                        if ($category_id) {
                            $category_ids[] = $category_id->term_id;
                        } else {
                            // Create new category if it does not exist
                            $new_category = wp_insert_term(sanitize_text_field($product['item_category']), 'product_cat');
                            if (!is_wp_error($new_category)) {
                                $category_ids[] = $new_category['term_id'];
                            }
                        }
                    }
                    if (!empty($product['SUBMECATEGORY_NAME'])) {
                        $subcategory_id = get_term_by('name', sanitize_text_field($product['SUBMECATEGORY_NAME']), 'product_cat');
                        if ($subcategory_id) {
                            $category_ids[] = $subcategory_id->term_id;
                        } else {
                            // Create new subcategory if it does not exist
                            $new_subcategory = wp_insert_term(sanitize_text_field($product['SUBMECATEGORY_NAME']), 'product_cat');
                            if (!is_wp_error($new_subcategory)) {
                                $category_ids[] = $new_subcategory['term_id'];
                            }
                        }
                    }
                    if (!empty($category_ids)) {
                        $new_product->set_category_ids($category_ids);
                    }

                    $new_product->save();
					$result[$sku]=$product;
					$result[$sku]['action']='insert';
                }
            }
			if(!empty($products['body'])){
				update_option('softone_synced_products', array_map('sanitize_text_field', $result));
				softone_log('sync_products', 'Products synchronized successfully.');
				return ['success' => true, 'message' => 'Products synchronized successfully.', 'products' => $result];
			}
			else {
				softone_log('sync_products', 'No products to be synchronized.');
				return ['success' => true, 'message' => 'No products to be synchronized.', 'products' => $result];
			}
           
        } else {
            softone_log('sync_products', 'Failed to synchronize products.');
            return ['success' => false, 'message' => 'Failed to synchronize products.'];
        }
    }
}
/*
Sync the orders from last sync date
we check only date_updated, since it is populated with creation time on creation
and update time on every update


*/
function softone_sync_orders() {
	$api = new Softone_API();
    if (class_exists('WooCommerce')) {
		$fromDate=$api->lastUpdateDate('orders');
		
		// $dateString3 = "2026-10-09T10:08:14Z";
		// $date = new DateTime($dateString3, new DateTimeZone('UTC'));
		
		// $argsCreated = array(
		
			// 'date_created' => '>' . ( $dateString1 ),
			// 'limit' => -1,
			// // 'type'         => 'shop_order_refund',
			// // 'status' =>array( 'wc-processing', 'wc-on-hold','wc-pending' ), 
			// //'wc-failed','wc-refunded','wc-cancelled','wc-completed'
			// //'return' => 'ids'
		// );
        $argsUpdated = array(
			'date_modified' => '>'. strtotime( $fromDate ), //here we check if date modified is later that the last time we checked, date time must be converted to timestamp 
			'limit' => -1,
			// 'type'         => 'shop_order_refund',
			// 'status' =>array( 'wc-processing', 'wc-on-hold','wc-pending' ), 
			//'wc-failed','wc-refunded','wc-cancelled','wc-completed'
			'return' => 'ids'
		);
        // $ordersCreated = wc_get_orders($argsCreated);
        $orders = wc_get_orders($argsUpdated);
		
		if($orders){
			foreach ($orders as $orderID) {
				$order=wc_get_order( $orderID );
				
				//CHECK IF WE HAVE SOFT1 ORDER ID $order->meta_exists( 'your_meta_key_here' )
				if(!$order->meta_exists( '_S1SaleID_meta_key' ) || !empty($order->get_meta( '_S1SaleID_meta_key', true))){ 
					
					//FETCH CUSTOMER DATA FROM SOFT1
					$res=$api->fetchCustomer(['email'=>$order->get_billing_email()]);
				
					//an den yparxei o pelatis sto s1
					//prepei na dhmiourgiso ton pelati kai tha epistrepsi ton kodiko soft1 customer id 
					//sti synexeia prepei na apothikeuso ton kodiko pelati sto eidiko pedio tou WooCommerce, '_S1CustomerID_meta_key'
					//sozoume kai enimeronoume to timestamp telefteas enimerosis
					
					//CREATE CUSTOMER IN SOFT1
					if(!$res && !isset($res['cust_ID'])){ 
						
						$response=$api->create_customer($order);
						
						if($response['success'] && isset($response['customer_ID'])){
							//SAVE SOFT1 CUSTOMER ID IN ORDER META DATA
							$order->update_meta_data( '_S1CustomerID_meta_key', $response['customer_ID'] );
							$order->save();
							
							//$fromDate=$api->lastUpdateDate('orders');
							
							softone_log('sync_orders', 'New customer created for order ID:'.$orderID.', successfully.');
						}
						else{
							//IF WE FAIL TO CREATE THE CUSTOMER WE LOG THE FAILURE AND SKIP TO THE NEXT ORDER
							softone_log('sync_orders', 'Failed to create customer for order ID:'.$orderID.', order skipped sync with softone.');
							continue;
						}
					}
					
					//UPDATE CUSTOMER DATA
					else{ //update the customer
						//an yparxei enimeronoyme ta stoixeia tou kai to customerID sto meta tou order
						
						//SAVE SOFT1 CUSTOMER ID IN ORDER META DATA
						$order->update_meta_data( '_S1CustomerID_meta_key', $response['customer_ID'] );
						$order->save();
						
						//UPDATE CUSTOMER DATA IN SOFT1 FROM ORDER DATA
						$response=$api->update_customer($res,$order);
						
						// $fromDate=$api->lastUpdateDate('orders');
						softone_log('sync_orders', 'Customer data for order ID:'.$orderID.', updated successfully.');
					}
					//an de exoume soft order id stin trexousa woocommerce paraggelia tote tha kanoyme create sale
				
					$response=$api->create_sale($order);
					if($response['success'] && isset($response['ID'])){
						$order->update_meta_data( '_S1SaleID_meta_key', $response['ID'] );
						$order->save();
						softone_log('sync_orders', 'New Order with WC_ID:'.$orderID.' and Soft1_ID:'.$response['ID'].', created successfully.');
					}
					else{
						softone_log('sync_orders', 'New Order with WC_ID:'.$orderID.' failed to be created in Soft1.');
					}
				}
				else{
					$api->update_sale($order);
					
				}
				//an exoume epitixia sth dimiourgia pelati
				//meta prepei na dhmiourgiso thn paragelia sto soft1
				
				//meta ti dimiourgia ths paraggelias pernoyme epistrofi ton kwdiko paraggelias soft1 
				//prepei na ton apothikeuso se eidiko pedio sthn paraggelia woocommerce	
				// $order->update_meta_data( '_S1SaleID_meta_key', '323' );
				// $order->save();
				// $fromDate=$api->lastUpdateDate('orders');	
				
				
				
				
				
				
				
				// // $api->create_order($order);
			}
		}
		else{
			softone_log('sync_orders', 'Orders synchronized successfully.');
			return ['success'=>true,'message'=>'No orders to sync','orders'=>$orders,'lastUpdateDate'=>$fromDate,'args'=>$argsUpdated];
		}
        softone_log('sync_orders', 'Orders synchronized successfully.');
        return ['success'=>true,'message'=>'Orders synchronized successfully ','orders'=>$orders,'lastUpdateDate'=>$fromDate,'args'=>$argsUpdated];
    }
}






function softone_sync_customers() {
    if (class_exists('WooCommerce')) {
        $api = new Softone_API();
		
		$customer_query = new WP_User_Query(
		  array(
			 'fields' => 'ID',
			 'role' => 'customer',         
		  )
		);
		$customers=$customer_query->get_results();
		
		foreach ($customers as $c=>$customer_id){
			$customer = new WC_Customer( $customer_id );
			$customer_order_count=$customer->get_order_count();
			$customer_email=$customer->get_billing_email();
			$customer_first_name=$customer->get_billing_first_name();
			$response=$api->fetchCustomer(['email'=>$customer_email]);
			if($response && isset($response['body']) && $response['counter']>0){
				$found_customers[$customer_id]=$response['body'][0];
				// $found_customers[$customer_id]['status'] = 'update';
				// $found_customers['woo']=$customer;
			}
			else {
				// $found_customers['woo']=$customer;
				$found_customers[$customer_id]=[
												'cust_ID'=>$customer_id,
												'cust_Code'=>'Orders:'.$customer_order_count,
												'cust_descr'=>$customer_first_name,
												'custAfm'=>'Need to',
												'cust_phone'=>'be',
												'cust_Email'=>$customer_email,
												'company_branch'=>''];
				// $found_customers[$customer_id]['status'] = 'insert';
				
				//$found_customers['woo']=$customer;
			}
		}
	
		return ['success' => true, 'message' => 'No customers to be synchronized.', 'customers' => $found_customers];

    }
}

function test_sku_item_code() {
	if (class_exists('WooCommerce')) {
		$api = new Softone_API();
		$products = wc_get_products( array(
		'limit'    => 10,
		'order'    => 'DESC',
		'orderby'  => 'meta_value',
		'meta_key' => '_sku',
		) );
		$found=array();
		foreach ( $products as $product ) {
			//printf( '%s (%s)<br>', $product->get_name(), $product->get_sku() );
			$sku=$product->get_sku();
			$response=$api->findSoft1Product($sku);
			$found[$sku]=$response;
		}
		// $found['products']=$products;
		return ['success' => true, 'message' => 'Skus found list.', 'skus' => $found];
	}
}

