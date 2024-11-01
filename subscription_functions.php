<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }


function WC_Yourpay_add_subscription_product($order) {
    $items = $order->get_items();
    foreach ( $items as $item ) {
        $product_name = $item['name'];
        $product_id = $item['product_id'];
        $product_variation_id = $item['variation_id'];
        $addProductAsSubscription = get_post_meta( $item['product_id'] , '_yourpay_subscription_active' );
        if($addProductAsSubscription[0] == "yes") {
            $subscription_value     = get_post_meta( $item['product_id'] ,'_yourpay_subscription_value' );
            $subscription_date      = get_post_meta($item['product_id']  ,'_yourpay_subscription_date');
            $subscription_repeat    = get_post_meta($item['product_id']  ,'_yourpay_subscription_date_repeat');
            $subscription_id        = get_post_meta($order->id, 'subscription_rg_code', true);
            
            $put = array();
            $put["function"]            = "rebilling_products";
            $put["subscription_id"]     = $subscription_id;
            $put["subscription_product"]= $item['product_id'];
            $put["subscription_value"]  = number_format($subscription_value[0],2,"","");
            $put["subscription_date"]   = $subscription_date[0];
            $put["subscription_period"] = $subscription_repeat[0];
            $put["action"]              = "put";
            var_dump(v4requestresponse($put));
            
        }
    }
}
function WC_Yourpay_get_subscription_data($subscription_id) {
            $get = array();
            $get["function"]            = "rebilling_products";
            $get["subscription_id"]     = $subscription_id;
            $get["action"]              = "take";
            return v4requestresponse($get);
    
}
function WC_Yourpay_cancel_subscription($subscription_id,$product_id) {
    
            $get = array();
            $get["function"]            = "rebilling_products";
            $get["subscription_id"]     = $subscription_id;
            $get["subscription_product"]= $product_id;
            $get["action"]              = "cancel";
            
            return v4requestresponse($get);
    
}
function WC_Yourpay_rebill_subscription($subscription_id,$product_id,$paymentid,$tid,$merchantnumber,$amount) {
            $get = array();
            $get["function"]            = "rebilling_products";
            $get["subscription_id"]     = $subscription_id;
            $get["subscription_product"]= $product_id;
            $get["paymentid"]           = $paymentid;
            $get["tid"]                 = $tid;
            $get["merchantnumber"]      = $merchantnumber;
            $get["amount"]              = $amount;
            $get["action"]              = "rebill";
            
            return json_decode(json_decode(v4requestresponse($get)));
    
}
function WC_Yourpay_rebill_next($period_type, $period_date = "1", $last = 0) {
    
    if($period_type == "0") {
        return strtotime(date("d-m-Y", $last+86400));
    }
    elseif($period_type == "1") {
        return strtotime(date("d-m-Y", $last+86400*7));
    }
    elseif($period_type == "2") {
        return strtotime(date("d-m-Y", $last+86400*14));
    }
    elseif($period_type == "3") {
        
        if($last == 0) {
            $next = 0;
        } else {
            $monthlast = date("m", $last);
            $monthnext = $monthlast+1;
            $yearcapture = date("Y");
            if($monthnext == 13) {
                $monthnext = "01";      
                $yearcapture = $yearcapture+1;
            }
            $next = strtotime(date("d-".$monthnext."-$yearcapture"));
        }
        
        return $next;
    }
    elseif($period_type == "4") {
        
        if($last == 0) {
            $next = 0;
        } else {
            $monthlast = date("m", $last);
            $monthnext = $monthlast+3;
            $yearcapture = date("Y");
            if($monthnext > 12) {
                $monthnext = "01";      
                $yearcapture = $yearcapture+3;
            }
            $next = strtotime(date("d-".$monthnext."-$yearcapture"));
        }
        
        return $next;
    } 
    elseif($period_type == "5") {
        
        if($last == 0) {
            $next = 0;
        } else {
            $monthlast = date("m", $last);
            $monthnext = $monthlast+6;
            $yearcapture = date("Y");
            if($monthnext > 12) {
                $monthnext = "01";      
                $yearcapture = $yearcapture+6;
            }
            $next = strtotime(date("d-".$monthnext."-$yearcapture"));
        }
        
        return $next;
    } 
    elseif($period_type == "6") {
        
        if($last == 0) {
            $next = 0;
        } else {
            $monthlast = date("m", $last);
            $monthnext = $monthlast+12;
            $yearcapture = date("Y");
            if($monthnext > 12) {
                $monthnext = "01";      
                $yearcapture = $yearcapture+12;
            }
            $next = strtotime(date("d-".$monthnext."-$yearcapture"));
        }
        return $next;
    } else {
        return strtotime(date("d-m-Y", $last+86400));
    }    
}
function WC_Yourpay_create_order($oderid, $product_id, $amount) {
    

                    
                    $merchantnumber = get_post_meta($oderid, 'MerchantNumber', true);                    
                    $tid = get_post_meta($oderid, 'timeid', true);                    
                    $pid = get_post_meta($oderid, 'Transaction ID', true);                    
                    $subscription_key = get_post_meta($oderid, 'subscription_rg_code', true);                    
                    $data = WC_Yourpay_rebill_subscription($subscription_key, $product_id, $pid, $tid, $merchantnumber, $amount);
                    
                    if(isset($data->state) && $data->state == "1") {
                        $order_id = $oderid;
                        $billing_first_name =  get_post_meta($order_id,'_billing_first_name',true);
                        $billing_last_name = get_post_meta($order_id,'_billing_last_name',true);
                        $billing_company = get_post_meta($order_id,'_billing_company',true);
                        $billing_address = get_post_meta($order_id,'_billing_address_1',true);
                        $billing_address2 = get_post_meta($order_id,'_billing_address_2',true);
                        $billing_city = get_post_meta($order_id,'_billing_city',true);
                        $billing_postcode = get_post_meta($order_id,'_billing_postcode',true);
                        $billing_country = get_post_meta($order_id,'_billing_country',true);
                        $billing_state = get_post_meta($order_id,'_billing_state',true);
                        $billing_email = get_post_meta($order_id,'_billing_email',true);
                        $billing_phone = get_post_meta($order_id,'_billing_phone',true);                        
                        
                        $myProduct = new WC_Product($product_id);
                        $order = wc_create_order();
                        
                        $address = array(
                            'first_name' => $billing_first_name,
                            'last_name'  => $billing_last_name,
                            'company'    => $billing_company,
                            'email'      => $billing_email,
                            'phone'      => $billing_phone,
                            'address_1'  => $billing_address,
                            'address_2'  => $billing_address2, 
                            'city'       => $billing_city,
                            'state'      => $billing_state,
                            'postcode'   => $billing_postcode,
                            'country'    => $billing_country
                        );
                        
                        $order->set_address( $address, 'billing' );
                        $order->set_address( $address, 'shipping' );
                        
                        $args['totals']['subtotal'] = number_format($amount/100);
                        $args['totals']['total'] = number_format($amount/100);

                        $order->add_product( get_product( $product_id ), 1, $args );                        
                        
                        $order->calculate_totals();                        

                        $order_id = trim(str_replace('#', '', $order->get_order_number()));
                        
			update_post_meta($order_id, 'Transaction ID', $data->payment->tid);
			update_post_meta($order_id, 'timeid', $data->payment->timeid);
			update_post_meta($order_id, 'MerchantNumber', $data->payment->MerchantNumber);
                        
                        $order->payment_complete();
                     
                        return true;
                    } else 
                        return false;
                    
    
}
function WC_Yourpay_rebill_daily() {
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
                
                foreach($orders as $key) {
                    $subscription_key = get_post_meta($key->ID, 'subscription_rg_code', true);
                    $subsdata = WC_Yourpay_get_subscription_data($subscription_key);
                    $json_products = json_decode(json_decode($subsdata));
                    if($json_products != "0") {
                        if($json_products->state == "0")
                            continue;
                        
                        $order_data = new WC_Order($key->ID);
                        $product_object = get_post($json_products->subscription_product);
                        $firstname = $order_data->billing_first_name;
                        $lastname = $order_data->billing_last_name;
                        $expected = WC_Yourpay_rebill_next($json_products->subscription_period, $json_products->subscription_date, $json_products->subscription_last);
                        if($expected < time()) {
                            WC_Yourpay_create_order($key->ID, $json_products->subscription_product, $json_products->subscription_value);
                        }
                    }
                }
    
}