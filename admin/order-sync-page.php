<?php
/**
 * Displays the order synchronization page for the Softone WooCommerce Integration.
 */
function softone_orders_page() {
	$api = new Softone_API();
    if (isset($_POST['sync_orders']) && check_admin_referer('softone_sync_orders_action', 'softone_sync_orders_nonce')) {
        $result = softone_sync_orders();
		$orders = $result['orders'];
		// echo date('d.m.Y H:i:s',trim( $result['args']['date_modified'],'>'));
        echo '<div class="notice notice-success"><p>' . $result['message'] .' at '.// date('d-m-Y H:i:s',strtotime($result['lastUpdateDate'])).
		'</p></div>';
		// echo "<pre>";
		// // foreach ($result['orders'] as $order){
			// // // echo $order->get_billing_email();
			// print_r($result['found']);
		// }
    }
    ?>
    <div class="wrap">
        <h1>Sync WooCommerce Orders to Softone</h1>
        <form method="post">
            <?php wp_nonce_field('softone_sync_orders_action', 'softone_sync_orders_nonce'); ?>
            <input type="hidden" name="sync_orders" value="1">
            <?php submit_button('Sync Orders'); ?>
        </form>
        <?php
        // Assuming softone_get_orders() function retrieves orders from Softone
     
	   // echo "<pre>";
	   // print_r($orders);
        if ($orders) {
            ?>
            <h2>Synchronized Orders</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Customer Name</th>
                        <th>Customer Email</th>
                        <th>Customer Address</th>						
                        <th>Customer City</th>						
                        <th>Customer State</th>						
                        <th>Customer Country</th>						
                        <th>Customer Postcode</th>						
                        <th>Date Created</th>
                        <th>Date Updated</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Customer S1 ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $orderID):
						$order=wc_get_order( $orderID );	
						$state=$api->getDistrictName($order->get_billing_state());						
								?>
                    <tr>
                        <td><?php echo esc_html($key); ?></td>
                        <td><?php echo esc_html($key); ?></td>
                        <td><?php echo esc_html($order->get_customer_id()); ?></td>
                        <td><?php echo esc_html($order->get_billing_first_name().'-'.$order->get_billing_last_name()); ?></td>
                        <td><?php echo esc_html($order->get_billing_email()); ?></td>
                        <td><?php echo esc_html($order->get_billing_address_1()); ?></td>
                        <td><?php echo esc_html($order->get_billing_city()); ?></td>
                        <td><?php echo esc_html($state); ?></td>
                        <td><?php echo esc_html($order->get_billing_country()); ?></td>
                        <td><?php echo esc_html($order->get_billing_postcode()); ?></td>
                        <td><?php echo esc_html($order->get_date_created()); ?></td>
                        <td><?php echo esc_html($order->get_date_modified()); ?></td>
                        <td><?php echo esc_html($order->get_status()); ?></td>
                        <td><?php echo esc_html($order->get_total()); ?></td>
                        <td><?php echo esc_html($order->get_meta('_S1customerID_meta_key',true)); ?></td>
                    </tr>
						<?php 
							foreach ( $order->get_items() as $item_id => $item ):
							?>
								<tr>
									<td>pId:<?php echo esc_html($item->get_product_id());?></td>
									<td>pName:<?php echo esc_html($item->get_name());?></td>
									<td>pPrice:<?php echo esc_html($item->get_product()->get_price());?></td>
									<td>pType:<?php echo esc_html($item->get_type());?></td>
									<td>pSKU:<?php echo esc_html($item->get_product()->get_sku());?></td>
									<td>pWeight:<?php echo esc_html($item->get_product()->get_weight());?></td>
									<td>pTotal:<?php echo esc_html($item->get_total());?></td>
									<td>pType:<?php echo esc_html($item->get_type());?></td>
									<td>pId:<?php echo esc_html($item->get_product_id());?></td>
									<td>pName:<?php echo esc_html($item->get_name());?></td>
									<td>pTotal:<?php echo esc_html($item->get_total());?></td>
									<td>pType:<?php echo esc_html($item->get_type());?></td>
									
								</tr>
							<?php
							endforeach;
						?>
                    <?php endforeach; ?>
					
                </tbody>
            </table>
            <?php
        }
        ?>
    </div>
    <?php
}
?>