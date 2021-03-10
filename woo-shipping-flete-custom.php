<?php
 
/**
 * Plugin Name: WooCommerce Tramusa Shipping
 * Plugin URI: https://www.neuestudio.mx/
 * Description: Custom Shipping Method for WooCommerce using Tramusa API
 * Version: 1.0.0
 * Author: Alain Xchel (@alainxps) || Neuestudio
 * Author URI: https://www.neuestudio.mx/
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: tutsplus
 */
 
if ( ! defined( 'WPINC' ) ) {
    die;
}
 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    function tutsplus_shipping_method() {
        if ( ! class_exists( 'TutsPlus_Shipping_Method' ) ) {
            class TutsPlus_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'tutsplus'; 
                    $this->method_title       = __( 'Tramusa Shipping', 'tutsplus' );  
                    $this->method_description = __( 'Custom Shipping Method for Tramusa', 'tutsplus' );

                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries = array(
                        'MX', // México
                        );
 
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Tramusa Shipping', 'tutsplus' );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->form_fields = array(
                        'enabled' => array(
                              'title' => __( 'Habilitar', 'tutsplus' ),
                              'type' => 'checkbox',
                              'description' => __( 'Habilitar este envío.', 'tutsplus' ),
                              'default' => 'yes'
                              ),
                 
                        'title' => array(
                            'title' => __( 'Título', 'tutsplus' ),
                              'type' => 'text',
                              'description' => __( 'Title a mostrar en el sitio', 'tutsplus' ),
                              'default' => __( 'Tramusa Shipping', 'tutsplus' )
                              ),

                        'weight' => array(
                            'title' => __( 'Peso máximo (kg)', 'tutsplus' ),
                              'type' => 'number',
                              'description' => __( 'Peso máximo permitido por envío', 'tutsplus' ),
                              'default' => 100
                              ),
                    );
 
                }
 
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package ) {

                  /* Obtener token (válido por 14 días) */
                  /*$url = 'http://apitramusa.tramusa.com/token';

                  $response = wp_remote_post( $url, array(
                    'method' => 'POST',
                    'timeout' => 0,
                    'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                    'body' => array(
                        'grant_type' => 'password',
                        'username' => 'Neuestudio',
                        'password' => 'Muebles#1010#Artex',
                     ),
                    )
                  );

                  if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    echo "Algo salió mal: $error_message";
                  }
                  else {
                    echo 'Respuesta:<pre>';
                    print_r( $response );
                    echo '</pre>';
                    echo '<script>';
                    echo 'console.log('. $response .')';
                    echo '</script>';
                  }*/
                  /* Obtener token (válido por 14 días) */

                  $weight = 0;
                  $length = 0;
                  $width = 0;
                  $height = 0;
                  $cost = 0;
                  $val_mercancia = 0;
                  $country = $package["destination"]["country"];
                  $cp_destino = $package["destination"]["postcode"];

                  foreach ( $package['contents'] as $item_id => $values ) { 
                    $_product = $values['data']; 
                    $weight = $weight + $_product->get_weight() * $values['quantity'];
                    $length = $length + $_product->get_length() * $values['quantity'];
                    $width = $width + $_product->get_width() * $values['quantity'];
                    $height = $height + $_product->get_height() * $values['quantity'];
                    $val_mercancia = $val_mercancia + $_product->get_price() * $values['quantity'];
                  }

                  $weight = wc_get_weight( $weight, 'kg' );

                  /* Cotizar servicio */
                  /* Datos requeridos
                  Origen:         44470
                  Destino:        Código postal
                  ValorMercancia: $
                  Peso:           kg
                  Largo:          cm
                  Alto:           cm
                  Ancho:          cm
                  Descripción:    Muebles
                  */
                  /*$url = 'http://apitramusa.tramusa.com/api/Cotizacion?origen=44470&destino=64060&valormercancia=10000&descripcion=muebles&piso=1&elevador_escaleras=Escaleras';*/
                  $url = 'http://apitramusa.tramusa.com/api/Cotizacion?peso='.$weight.'&largo='.$length.'&alto='.$height.'&ancho='.$width.'&origen=44470&destino='.$cp_destino.'&valormercancia='.$val_mercancia.'&descripcion=muebles&piso=1&elevador_escaleras=Escaleras';

                  $response = wp_remote_post( $url, array(
                    'method' => 'POST',
                    'timeout' => 0,
                    'headers' => array(
                        'Authorization' => 'Bearer z0WJ-7t1CsWwz3kpYVhVblUJwGzPFRCmxr_bFVgZyjY1gUvHyPskSYeuDMNLsOInRH81oorQIYajbiN0ZejmNewk6sYg2rr7xX5rVpxUvMwFWOl_gYqemhDbT8dCFqnprpVotE32DBz95vYeXB7VdZgafTfksmcfte7KZixMd80XeVgI9ekHrpQQCmUghzZKGBFkCgCtKETmPpiHR6JBP61ZF97b-eBFs4WAVSlZVKr5DsNJ2s87kwabn42_MKk5WCTwV10RuhTX1NBeR0GqjFgUuLIMa4YibhfJ2LlBUTSEJbwZ6G5g3z0cSD7UsiFIVYsGSKcP8BfcW5XWaHZWEQdtaWE2Sq12wvD0916WuxZKQ7khTgpLVMCnFX4lVCrI_tCu0lX_9PInA-Fw7T2kAJ7f7uVWoj43ZdzfzXQ7JBiPM0GnYfmukzy_oP1zQGuSuRjRUXP9hm6WS8kSfIxh2WyZf4xFk7UPFyJ4i55eaEifxxH77IAwqM-6x_WWjMIB8y-3IGjeY6Q8eEcqLAi7AQ' ),
                    )
                  );

                  if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    echo "<pre>Algo salió mal: $error_message <br> Por favor, recarga la página y si el error persiste comunícate con nosotros a soporte@mueblesartex.com</pre>";
                  }
                  else {
                    echo 'Respuesta:<pre>';
                    print_r( $response );
                    echo '</pre>';

                    $body = wp_remote_retrieve_body( $response );
                    // On success (i.e. `$body` is a valid JSON string), `$responseData` would be an
                    // `ARRAY`.
                    $responseData = ( ! is_wp_error( $response ) ) ? json_decode( $body, true ) : null;
                    /*var_dump( intval($responseData['Servicio']) );*/
                    var_dump( $responseData );
                  }
                  /* Cotizar servicio */

                  $cost = $responseData['Servicio'];

                  $rate = array(
                    'id' => $this->id,
                    'label' => $this->title,
                    'cost' => $cost
                  );

                  $this->add_rate( $rate );
                    
                }
            }
        }
    }
 
    add_action( 'woocommerce_shipping_init', 'tutsplus_shipping_method' );
 
    function add_tutsplus_shipping_method( $methods ) {
        $methods[] = 'TutsPlus_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_tutsplus_shipping_method' );

    /* Establecer límite de peso */
    function tutsplus_validate_order( $posted )   {

        $packages = WC()->shipping->get_packages();
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
         
        if( is_array( $chosen_methods ) && in_array( 'tutsplus', $chosen_methods ) ) {
             
            foreach ( $packages as $i => $package ) {
         
                if ( $chosen_methods[ $i ] != "tutsplus" ) {
                    continue;
                }
         
                $TutsPlus_Shipping_Method = new TutsPlus_Shipping_Method();
                $weightLimit = (int) $TutsPlus_Shipping_Method->settings['weight'];
                $weight = 0;
         
                foreach ( $package['contents'] as $item_id => $values ) { 
                    $_product = $values['data']; 
                    $weight = $weight + $_product->get_weight() * $values['quantity']; 
                }
         
                $weight = wc_get_weight( $weight, 'kg' );
                
                if( $weight > $weightLimit ) {
         
                        $message = sprintf( __( 'Lo sentimos, %d kg excede el máximo peso permitido por envío de %d kg for %s', 'tutsplus' ), $weight, $weightLimit, $TutsPlus_Shipping_Method->title );   
                        $messageType = "error";
         
                        if( ! wc_has_notice( $message, $messageType ) ) {
                            wc_add_notice( $message, $messageType );
                        }
                }
            }       
        } 
    }

    add_action( 'woocommerce_review_order_before_cart_contents', 'tutsplus_validate_order' , 10 );

    add_action( 'woocommerce_after_checkout_validation', 'tutsplus_validate_order' , 10 );

}