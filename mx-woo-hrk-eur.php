<?php
/*
Plugin Name: MX WooCommerce HRK + EUR
Plugin URI: https://media-x.hr
Description: Dvojno iskazivanje cijena u postupku uvođenja eura
Version: 3.2
Author: Media X
WC tested up to: 7.2
Author URI: https://media-x.hr
*/

if ( !defined('ABSPATH') ) { 
    die;
}

function get_eur_rate() {
        $eur_rate = 7.53450;      
        return $eur_rate;
}
function priceToFloat($s)
{
    // convert "," to "."
    $s = str_replace(',', '.', $s);

    // remove everything except numbers and dot "."
    $s = preg_replace("/[^0-9\.]/", "", $s);

    // remove all seperators from first part and keep the end
    $s = str_replace('.', '',substr($s, 0, -3)) . substr($s, -3);

    // return float
    return (float) $s;
} 
function FloatRate($s)
{
    // convert "," to "."
    $s = str_replace(',', '.', $s);


    // return float
    return (float) $s;
} 
function convert_to_eur($price) {
    $rate = get_eur_rate();
    $new_price = $price / FloatRate($rate);
   return number_format($new_price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
}
function convert_to_hrk($price) {
    $rate = get_eur_rate();
	
	if (get_option('hrk_rounding') === 'ceil') {
		$new_price = $price * FloatRate($rate);
		$new_price = ceil ($new_price);
	}
	elseif (get_option('hrk_rounding') === 'round') {
		$new_price = $price * FloatRate($rate);
		$new_price = round ($new_price);
	}
	else {
    $new_price = $price * FloatRate($rate);
	}
   return number_format($new_price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
}




add_filter( 'wc_price', 'mx_custom_price_format', 10, 4 );
function mx_custom_price_format( $formatted_price, $price, $args, $unformatted_price  ) {
    $current_currency = get_woocommerce_currency();
    if($current_currency == 'HRK') {
    $price_eur = convert_to_eur($unformatted_price);
	
    $formatted_price_eur = "<span class=\"woocommerce-Price-amount amount amount-eur\"> <small>($price_eur €)</small> </span>";

    return $formatted_price . $formatted_price_eur;
	 }
    elseif($current_currency == 'EUR' && get_locale() == 'hr') {
    $price_hrk = convert_to_hrk($unformatted_price);
    $formatted_price_eur = "<span class=\"woocommerce-Price-amount amount amount-eur\"> <small>($price_hrk kn)</small> </span>";

    return $formatted_price . $formatted_price_eur;
	 }	 else {
    return $formatted_price;
    }
}

function filter_woocommerce_get_formatted_order_total( $formatted_total, $order ) { 
  
        return $formatted_total; 
    
}
         
add_filter( 'woocommerce_get_formatted_order_total', 'filter_woocommerce_get_formatted_order_total', 10, 2 ); 
add_filter( 'woocommerce_order_shipping_to_display', 'filter_woocommerce_get_formatted_order_total', 10, 2 ); 

function filter_woocommerce_order_subtotal_to_display($subtotal, $compound, $order) {
    return filter_woocommerce_get_formatted_order_total($subtotal, $order);
}
add_filter( 'woocommerce_order_subtotal_to_display', 'filter_woocommerce_order_subtotal_to_display', 10, 3 ); 


function original_wc_price( $price, $args = array() ) {
    $args = apply_filters(
      'wc_price_args', wp_parse_args(
        $args, array(
          'ex_tax_label'       => false,
          'currency'           => '',
          'decimal_separator'  => wc_get_price_decimal_separator(),
          'thousand_separator' => wc_get_price_thousand_separator(),
          'decimals'           => wc_get_price_decimals(),
          'price_format'       => get_woocommerce_price_format(),
        )
      )
    );
  
    $unformatted_price = $price;
    $negative          = $price < 0;
    $price             = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * -1 : $price ) );
    $price             = apply_filters( 'formatted_woocommerce_price', number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] ), $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );
  
    if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
      $price = wc_trim_zeros( $price );
    }
  
    $formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], '<span class="woocommerce-Price-currencySymbol">' . get_woocommerce_currency_symbol( $args['currency'] ) . '</span>', $price );
    $return          = '<span class="woocommerce-Price-amount amount">' . $formatted_price . '</span>';
  
    if ( $args['ex_tax_label'] && wc_tax_enabled() ) {
      $return .= ' <small class="woocommerce-Price-taxLabel tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
    }
  
    return $return;
  }

/* Override email template  */
function mx_eur_plugin_path() {

  return untrailingslashit( plugin_dir_path( __FILE__ ) );
}

add_filter( 'woocommerce_locate_template', 'mx_woocommerce_locate_template', 10, 3 );

function mx_woocommerce_locate_template( $template, $template_name, $template_path ) {
	  global $woocommerce;

	  $_template = $template;

	  if ( ! $template_path ) $template_path = $woocommerce->template_url;

	  $plugin_path  = mx_eur_plugin_path() . '/woocommerce/';

	  // Look within passed path within the theme - this is priority
	  $template = locate_template(

		array(
		  $template_path . $template_name,
		  $template_name
		)
	  );

	  if ( ! $template && file_exists( $plugin_path . $template_name ) )
		$template = $plugin_path . $template_name;

	  if ( ! $template )
		$template = $_template;

	  return $template;
}
/* Prikaz korištenog tečaja */
//stranica proizvoda
//add_action( 'woocommerce_after_add_to_cart_button' , 'dual_price_after_add_to_cart', 5 );
add_action( 'woocommerce_product_meta_start' , 'dual_price_after_add_to_cart', 10 );

 
function dual_price_after_add_to_cart() {
	if (get_option('enable_product_info') === 'yes') {
   echo '<span class="rate_product_page">TEČAJ: 1 EUR = 7,53450 HRK</span>';
   }
   
}
//stranica trgovine, kategorije, arhive
add_action( 'woocommerce_after_shop_loop_item' , 'dual_price_shop_loop', 5 );
 
function dual_price_shop_loop() {
	if (get_option('enable_archive_info') === 'yes') {
   echo '<span class="rate_archive_page"><small>1 EUR = 7,53450 HRK</small></span>';
   }
  // $my_data = get_option( 'enable_product_info' );
  // echo var_dump( $my_data );
}
// stranica košarice
add_action( 'woocommerce_proceed_to_checkout' , 'dual_price_cart_page', 1 );
 
function dual_price_cart_page() {
	if (get_option('enable_cart_info') === 'yes') {
   echo '<span class="rate_cart_page"><small>TEČAJ: 1 EUR = 7,53450 HRK</small></span>';
   }
   
}
/* Checkout messages */
add_action( 'woocommerce_review_order_before_payment', 'mx_notice_shipping', 5 );
function mx_notice_shipping() {
 
  $dual_price_checkout = get_option( 'show_checkout_dual_info' );
  
  if (!empty($dual_price_checkout)) {
  echo '<div class="foreign-currency-checkout woocommerce-info">' . $dual_price_checkout . '</div>';
   }
	 
}
/* Admin opcije */
add_filter( 'woocommerce_get_sections_products' , 'dual_prices_settings_tab' );

function dual_prices_settings_tab( $settings_tab ){
     $settings_tab['dual_prices_notices'] = __( 'Dvojni prikaz cijena' );
     return $settings_tab;
}

add_filter( 'woocommerce_get_settings_products' , 'dual_prices_get_settings' , 10, 2 );

function dual_prices_get_settings( $settings, $current_section ) {
         $custom_settings = array();
         if( 'dual_prices_notices' == $current_section ) {

              $custom_settings =  array(

				   array(
					    'name' => __( 'Dvojni prikaz cijena' ),
					    'type' => 'title',
					    'desc' => __( 'Opcije prikaza korištenog tečaja na stranici proizvoda, arhive, košarice i naplate' ),
					    'id'   => 'dvojni_prikaz' 
				       ),
				   array(
						'name' => __( 'Stranica proizvoda' ),
						'type' => 'checkbox',
						'desc' => __( 'Prikaži info o fiksnom tečaju na stranici proizvoda'),
						'id'	=> 'enable_product_info',
						'default'  => '',

					),
					 array(
						'name' => __( 'Stranica trgovine/arhive' ),
						'type' => 'checkbox',
						'desc' => __( 'Prikaži info o fiksnom tečaju na stranici trgovine/arhive'),
						'id'	=> 'enable_archive_info'

					),
					array(
						'name' => __( 'Stranica košarice' ),
						'type' => 'checkbox',
						'desc' => __( 'Prikaži info o fiksnom tečaju na stranici košarice'),
						'id'	=> 'enable_cart_info'

					),
					array(
						'name' => __( 'Stranica naplate' ),
						'type' => 'textarea',
				        'css' => 'height:100px;',
						'desc' => __( 'Npr. možete kopirati ovaj kod u polje niže <br><code>&lt;p&gt;&lt;strong&gt;Sva plaćanja biti će izvr&scaron;ena u hrvatskim kunama&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;&lt;small&gt;Iznos u eurima dobiva se konverzijom cijene po fiksnom tečaju HNB-a &lt;br&gt; 1 EUR = 7,53450 HRK&lt;/small&gt;&lt;/p&gt;</code>'),
						//'desc_tip' => true,
						'id'	=> 'show_checkout_dual_info'

					),
					array(
						'name' => __( 'Zaokruži cijene u kn' ),
						'type' => 'select',
						'desc' => __( 'Izaberite ako ste izvršili promjenu cijena iz kn u euro i želite zaokružiti informativnu cijenu u kn <br> <strong> round</strong>: 99.01->99,00 ili 99,50->100,00<br><strong>ceil</strong>: 99,01 kn ili 99,50 kn -> 100,00 kn'),
						/* 'desc_tip' => true, */
						'id'	=> 'hrk_rounding',
						'options' => array(
						   
								  'none' => __( 'Ne zaokružuj' ),
								  'round' => __('Zaokruži gore ili dolje na najbliži cijeli broj (round)'),
								  'ceil' => __( 'Zaokruži prema gore (ceil)' ),

						)

			),
				

					 array( 'type' => 'sectionend', 'id' => 'dvojni_prikaz' ),

	);

		return $custom_settings;
     } else {
        	return $settings;
    }

}

function dual_prices_action_links( $links ) {
		$settings = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=dual_prices_notices' ) ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>',
		);

		return array_merge( $settings, $links );
	}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'dual_prices_action_links' );

add_action('admin_head', 'mx_custom_admin_css');
function mx_custom_admin_css() {
	$current_currency = get_woocommerce_currency();
	if($current_currency == 'EUR') {
		  echo '<style>
			.amount-eur {
				display: none;
			  }
		  </style>';
  }
}
/* analitika */