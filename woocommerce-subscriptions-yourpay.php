<?php
/*
Plugin Name: WooCommerce Yourpay Subscriptions
Plugin URI: http://www.yourpay.dk
Description: Simplified Subscriptions options connected with Yourpay.io
Version: 1.0.8
Author: Yourpay
Author URI: http://www.yourpay.io/
Text Domain: yourpay.io
*/

add_action('plugins_loaded', 'add_wc_yourpay_subscriptions', 0);

function add_wc_yourpay_subscriptions() 
{
    	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
        
        include_once( 'subscription_functions.php' );
        /**
 	* Gateway class
 	**/
        add_action( 'wp', 'WC_Yourpay_rebill_daily' );
        
	class WC_Yourpay_Subscriptions extends WC_Payment_Gateway
	{	
		public function __construct()
		{
			global $woocommerce;
                 
                        $supports[] = "products";
			$this->id = 'yourpay_subscriptions';
			$this->method_title = 'Yourpay Subscriptions';
			$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/cards.png';
			$this->has_fields = false;                        
                        
                        
                }

	}

            function v4requestresponse($data)
                {

                    $url = "http://webservice.yourpay.dk/v4/".$data['function'];
                    $fields_string = '';
                    foreach($data as $key=>$value){
                        $fields_string[$key] = urlencode($value);
                    }

                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields_string));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $server_output = curl_exec ($ch);
                    curl_close ($ch);

                    return json_encode($server_output);
                } 
                
            function runSubscriptions() {
                
            }

            function woo_add_custom_general_fields() {

                global $woocommerce, $post;
                
                $i = 1;
                $dates[0] = "-- No Monthly Payment --";
                while($i < 32) {
                    $dates[$i] = "Day " . $i;
                    $i++;
                }
                
                $dates_yearly[0] = "-- Repeat time --";                
                $i = 1;
                $dates_repeat[0] = "Daily";
                $dates_repeat[1] = "Weekly";
                $dates_repeat[2] = "14 days between";
                $dates_repeat[3] = "Monthly";
                $dates_repeat[4] = "Quarterly";
                $dates_repeat[5] = "Half year";
                $dates_repeat[6] = "Yearly";


                echo woocommerce_wp_checkbox( 
                   array( 
                           'id'            => '_yourpay_subscription_active', 
                           'wrapper_class' => 'show_if_simple', 
                           'label'         => __('Subscription', 'woocommerce_yourpay_subscriptions' ), 
                           'description'   => __( 'Serve this product as subscription', 'woocommerce_yourpay_subscriptions' ) 
                           )
                   );

                echo woocommerce_wp_text_input( 
                    array( 
                            'id'      => '_yourpay_subscription_value', 
                            'label'   => __( 'Subscription Amount', 'woocommerce_yourpay_subscriptions' )
                            )
                    );

                
                echo woocommerce_wp_select( 
                    array( 
                            'id'      => '_yourpay_subscription_date', 
                            'label'   => __( 'Date for period start', 'woocommerce_yourpay_subscriptions' ), 
                            'options' => $dates
                            )
                    );

                
                echo woocommerce_wp_select( 
                    array( 
                            'id'      => '_yourpay_subscription_date_repeat', 
                            'label'   => __( 'Repeat time', 'woocommerce_yourpay_subscriptions' ), 
                            'options' => $dates_repeat
                            )
                    );
                
              }
                function woo_add_custom_general_fields_save( $post_id ){

                    $woocommerce_text_field         = $_POST['_yourpay_subscription_active'];
                    $woocommerce_date_field         = $_POST['_yourpay_subscription_date'];
                    $woocommerce_date_year_field    = $_POST['_yourpay_subscription_date_repeat'];
                    $woocommerce_value_field        = $_POST['_yourpay_subscription_value'];

                    update_post_meta( $post_id, '_yourpay_subscription_active',     esc_attr( $woocommerce_text_field  ) );
                    update_post_meta( $post_id, '_yourpay_subscription_date',       esc_attr( $woocommerce_date_field  ) );
                    update_post_meta( $post_id, '_yourpay_subscription_date_repeat',esc_attr( $woocommerce_date_year_field  ) );
                    update_post_meta( $post_id, '_yourpay_subscription_value',      esc_attr( $woocommerce_value_field ) );
	
                }
              
	function init_yourpay_subscriptions()
	{
		$plugin_dir = basename(dirname(__FILE__ ));
		load_plugin_textdomain('yourpay_subscriptions', false, $plugin_dir . '/languages/');
	}
	add_action('plugins_loaded', 'init_yourpay_subscriptions');
        add_action( 'woocommerce_product_options_general_product_data', 'woo_add_custom_general_fields' );
        add_action( 'woocommerce_process_product_meta', 'woo_add_custom_general_fields_save' );
        add_action('admin_menu', 'yourpay_subscriptions_setup_menu');
        function yourpay_subscriptions_setup_menu(){
                add_menu_page( 
                    __( 'Yourpay Subscriptions', 'textdomain' ),
                    'Yourpay Subscriptions',
                    'manage_options',
                    'yourpay-subscriptions',
                    'yourpay_admin_init',
                    'https://www.yourpay.io/img/favicon_webshops.png'
                ); 
        }

        function yourpay_admin_init(){
                echo "<h1>Yourpay Subscriptions!</h1>";

                echo WC_Yourpay_rebill_daily();

                if(isset($_GET['action']) && $_GET['action'] == "cancel") {
                    $data = WC_Yourpay_cancel_subscription($_GET['sid'],$_GET['pid']);
                }
                if(isset($_GET['action']) && $_GET['action'] == "capture") {
                    
                    WC_Yourpay_create_order($_GET['oid'], $_GET['pid'], $_GET['amount']);
                    
                }
                
                
                $args = array(
                    'post_type' => 'shop_order',
                    'post_status' => 'publish',
                    'posts_per_page' => 10,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'shop_order_status',
                            'field' => 'slug',
                            'terms' => array('completed' ,'processing')
                        )
                    ),
                    'meta_query' => array(
                            array(
                                'key' => 'subscription_rg_code'
                            )
                    )
                );
                $orders=get_posts($args);
                
                echo "<table>";
                    echo "<tr>";
                        echo "<th>OrderID</th>";
                        echo "<th>Subscription Key</th>";
                        echo "<th>Subscription Product</th>";
                        echo "<th>Subscription User</th>";
                        echo "<th>Subscription Value</th>";
                        echo "<th>Next action</th>";
                        echo "<th>Subscription Last</th>";
                        echo "<th>Actions</th>";
                    echo "</tr>";
                
                foreach($orders as $key) {
                    $subscription_key = get_post_meta($key->ID, 'subscription_rg_code', true);
                    $subsdata = WC_Yourpay_get_subscription_data($subscription_key);
                    $json_products = json_decode(json_decode($subsdata));
                    
                    if($json_products != "0") {
                        $order_data = new WC_Order($key->ID);
                        $product_object = get_post($json_products->subscription_product);
                        $firstname = $order_data->billing_first_name;
                        $lastname = $order_data->billing_last_name;
                        if(strlen($json_products->subscription_date)) {
                            $date = "0".$json_products->subscription_date;
                        } else {
                            $date = $json_products->subscription_date;
                        }
                        $expected = WC_Yourpay_rebill_next($json_products->subscription_period, $json_products->subscription_date, $json_products->subscription_last);
                        
                        echo "<tr>";
                            echo "<td>".$key->ID."</td>";
                            echo "<td>".$json_products->subscription_id."</td>";
                            echo "<td>".$product_object->post_title."</td>";
                            echo "<td>".$firstname." ".$lastname."</td>";
                            echo "<td>".number_format($json_products->subscription_value/100,2,",","")."</td>";
                            echo "<td>".date("d-m-Y 00:00:00", $expected)."</td>";
                            echo "<td>".date("d-m-y H:i", $json_products->subscription_last)."</td>";
                            echo "<td>";
                            
                            if($json_products->state == 1) {
                                echo 
                                    "<a href=\"admin.php?page=yourpay-subscriptions&action=capture&sid=".$json_products->subscription_id."&pid=".$json_products->subscription_product."&oid=".$key->ID."&ramount=".$json_products->subscription_value."\">Rebill now</a><br />"
                                    . "<a href=\"admin.php?page=yourpay-subscriptions&action=change&sid=".$json_products->subscription_id."&pid=".$json_products->subscription_product."\">Change subscription</a><br />"
                                    . "<a href=\"admin.php?page=yourpay-subscriptions&action=cancel&sid=".$json_products->subscription_id."&pid=".$json_products->subscription_product."\">Cancel Subscription</a>";                                
                            } else {
                                echo 
                                    "Subscription terminated";
                            }
                            echo "</td>";
                        echo "</tr>";

                    }
                }
                
                echo "</table>";
        }
        
        
	function WC_Yourpay_Subscriptions() 
	{
	    return new WC_Yourpay_Subscriptions();
	}
	
	if (is_admin())
            add_action('load-post.php', 'WC_Yourpay_Subscriptions');
        
}
