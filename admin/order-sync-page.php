<?php
/**
 * Displays the order synchronization page for the Softone WooCommerce Integration.
 */
function softone_orders_page() {
    if (isset($_POST['sync_orders']) && check_admin_referer('softone_sync_orders_action', 'softone_sync_orders_nonce')) {
        $result = softone_sync_orders();
        echo '<div class="notice notice-success"><p>' . $result['message'] . '</p></div>';
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
       $orders =$result['orders'];
	   // echo "<pre>";
	   // print_r($orders);
        if ($orders) {
            ?>
            <h2>Synchronized Orders</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Customer Name</th>
                        <th>Customer Email</th>
                        <th>Customer Address</th>						
                        <th>Customer City</th>						
                        <th>Customer State</th>						
                        <th>Customer Country</th>						
                        <th>Customer Postcode</th>						
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order_id):
								$order=wc_get_order( $order_id );
								?>
                    <tr>
                        <td><?php echo esc_html($order->get_id()); ?></td>
                        <td><?php echo esc_html($order->get_customer_id()); ?></td>
                        <td><?php echo esc_html($order->get_shipping_first_name().'-'.$order->get_shipping_last_name()); ?></td>
                        <td><?php echo esc_html($order->get_billing_email()); ?></td>
                        <td><?php echo esc_html($order->get_shipping_address_1()); ?></td>
                        <td><?php echo esc_html($order->get_shipping_city()); ?></td>
                        <td><?php echo esc_html($order->get_shipping_state()); ?></td>
                        <td><?php echo esc_html($order->get_shipping_country()); ?></td>
                        <td><?php echo esc_html($order->get_shipping_postcode()); ?></td>
                        <td><?php echo esc_html($order->get_date_created()); ?></td>
                        <td><?php echo esc_html($order->get_status()); ?></td>
                        <td><?php echo esc_html($order->get_total()); ?></td>
                    </tr>
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