<?php
/*
Plugin Name: Poud Payment
Plugin URI: http://proudcity.com/
Description: Declares an Payment custom post type.
Version: 1.0
Author: ProudCity
Author URI: http://proudcity.com/
License: GPLv2
*/

namespace Proud\Payment;

// Load Extendible
// -----------------------
if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php' );
}

class ProudPayment extends \ProudPlugin {

  /*public function __construct() {
    add_action( 'init', array($this, 'initialize') );
    add_action( 'admin_init', array($this, 'payment_admin') );
    add_action( 'save_post', array($this, 'add_payment_fields'), 10, 2 );
    //add_filter( 'template_include', 'payment_template' );
    add_action( 'rest_api_init', array($this, 'payment_rest_support') );
  }*/

  public function __construct() {
    /*parent::__construct( array(
      'textdomain'     => 'wp-proud-payment',
      'plugin_path'    => __FILE__,
    ) );*/

    $this->hook( 'init', 'create_payment' );
    $this->hook( 'admin_init', 'payment_admin' );
    //$this->hook( 'plugins_loaded', 'agency_init_widgets' );
    $this->hook( 'save_post', 'add_payment_fields', 10, 2 );
    $this->hook( 'rest_api_init', 'payment_rest_support' );
    //add_filter( 'template_include', array($this, 'agency_template') );
  }





  /*public function payment_template( $template_path ) {
      if ( get_post_type() == 'payment' ) {
          if ( is_single() ) {
              // We use the default post template here since we're just going to override it with Page Builder
              if ( $theme_file = locate_template( array ( 'content-payment.php' ) ) ) {
                  $template_path = $theme_file;
              } else {
                  $template_path = plugin_dir_path( __FILE__ ) . '/single-payment.php';
              }
          }
          elseif ( is_archive() ) {
              if ( $theme_file = locate_template( array ( 'loop-payment.php' ) ) ) {
                  $template_path = $theme_file;
              } else {
                  $template_path = plugin_dir_path( __FILE__ ) . '/archive-payment.php';
              }
          }
      }
      return $template_path;
  }*/


  public function create_payment() {
      $labels = array(
          'name'               => _x( 'Payments', 'post name', 'wp-payment' ),
          'singular_name'      => _x( 'Payment', 'post type singular name', 'wp-payment' ),
          'menu_name'          => _x( 'Payments', 'admin menu', 'wp-payment' ),
          'name_admin_bar'     => _x( 'Payment', 'add new on admin bar', 'wp-payment' ),
          'add_new'            => _x( 'Add New', 'payment', 'wp-payment' ),
          'add_new_item'       => __( 'Add New Payment', 'wp-payment' ),
          'new_item'           => __( 'New Payment', 'wp-payment' ),
          'edit_item'          => __( 'Edit Payment', 'wp-payment' ),
          'view_item'          => __( 'View Payment', 'wp-payment' ),
          'all_items'          => __( 'All Payments', 'wp-payment' ),
          'search_items'       => __( 'Search payment', 'wp-payment' ),
          'parent_item_colon'  => __( 'Parent payment:', 'wp-payment' ),
          'not_found'          => __( 'No payments found.', 'wp-payment' ),
          'not_found_in_trash' => __( 'No payments found in Trash.', 'wp-payment' )
      );

      $args = array(
          'labels'             => $labels,
          'description'        => __( 'Description.', 'wp-payment' ),
          'public'             => true,
          'publicly_queryable' => true,
          'show_ui'            => true,
          'show_in_menu'       => true,
          'query_var'          => true,
          'rewrite'            => array( 'slug' => 'payments' ),
          'capability_type'    => 'post',
          'has_archive'        => true,
          'hierarchical'       => false,
          'menu_position'      => null,
          'show_in_rest'       => true,
          'rest_base'          => 'payments',
          'rest_controller_class' => 'WP_REST_Posts_Controller',
          'supports'           => array( 'title', 'editor', 'thumbnail',)
      );

      register_post_type( 'payment', $args );
  }

  public function payment_admin() {
    add_meta_box( 'payment_meta_box',
      'Payment information',
      array($this, 'display_payment_meta_box'),
      'payment', 'normal', 'high'
    );
  }

  public function payment_rest_support() {
    register_api_field( 'payment',
          'meta',
          array(
              'get_callback'    => array( $this, 'payment_rest_metadata' ),
              'update_callback' => null,
              'schema'          => null,
          )
      );
  }

  /**
   * Alter the REST endpoint.
   * Add metadata to the post response
   */
  public function payment_rest_metadata( $object, $field_name, $request ) {
      $return = array();
      foreach ($this->payment_fields() as $key => $label) {
        if ($value = get_post_meta( $object[ 'id' ], $key, true )) {
          $return[$key] = $value;
        }
      }
      return $return;
  }

  public function payment_fields() {
    $return = array();

    switch (get_option( 'payment_type', 'link' )) {
      case 'stripe':
        $return['key'] = 'Stripe product key';
        break;
      default: //case 'link'
        $return['link'] = 'http://external.com/payment-type';
        break;
    }
  
    $return['icon'] = 'ex: fa-university';
    return $return;
  }


  public function display_payment_meta_box( $payment ) {
    foreach ($this->payment_fields() as $key => $label) {
      $value = esc_html( get_post_meta( $payment->ID, $key, true ) );
      ?>
      <div class="field-group">
        <label><?php print ucfirst($key); ?></label>
        <input type="textfield" name="payment_<?php print $key; ?>" value="<?php print $value; ?>" placeholder="<?php print $label; ?>" />
      </div>
      <?php
    }
  }

  /**
   * Saves contact metadata fields 
   */
  public function add_payment_fields( $id, $payment ) {
    if ( $payment->post_type == 'payment' ) {
      foreach ($this->payment_fields() as $field => $label) {
        //if ( !empty( $_POST['payment_'.$field] ) ) {  // @todo: check if it has been set already to allow clearing of value
          update_post_meta( $id, $field, $_POST['payment_'.$field] );
        //}
      }

    }
  }

} // class


new ProudPayment;
