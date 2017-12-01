<?php
/*
Plugin Name: Proud Payment
Plugin URI: http://proudcity.com/
Description: Declares an Payment custom post type.
Version: 1.0
Author: ProudCity
Author URI: http://proudcity.com/
License: Affero GPL v3
*/

//namespace Proud\Payment;

// Load Extendible
// -----------------------
if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php' );
}

class ProudPayment extends \ProudPlugin {

  public function __construct() {

    $this->hook( 'init', 'create_payment' );
    $this->hook( 'rest_api_init', 'payment_rest_support' );

    // Gravityforms integration
    add_filter( 'gform_add_field_buttons', array($this, 'add_invoice_field') );
    add_filter( 'gform_field_type_title' , array($this, 'invoice_title'), 10, 2);
    add_action( 'gform_field_input' , array($this, 'invoice_field_input'), 10, 5 );
    add_action( 'gform_editor_js_set_default_values', array($this, 'invoice_label') );
    add_action( 'gform_editor_js', array($this, 'gform_editor_js') );
  }

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
          'has_archive'        => false,
          'hierarchical'       => false,
          'menu_position'      => null,
          'show_in_rest'       => true,
          'rest_base'          => 'payments',
          'rest_controller_class' => 'WP_REST_Posts_Controller',
          'supports'           => array( 'title', 'editor', 'thumbnail',)
      );

      register_post_type( 'payment', $args );
  }

  public function payment_rest_support() {
    register_rest_field( 'payment',
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
   * Add metadata to t$forms = RGFormsModel::get_forms( 1, 'title' );he post response
   */
  public function payment_rest_metadata( $object, $field_name, $request ) {
    $PaymentMeta = new PaymentMeta;
    $return = $PaymentMeta->get_options( $object[ 'id' ] );
    return $return;
  }

  /**
   * Gravityforms integration
   * Tutorials: http://wpsmith.net/2011/how-to-create-a-custom-form-field-in-gravity-forms-with-a-terms-of-service-form-field-example/,
   * http://snippets.webaware.com.au/howto/overview-of-building-custom-fields-for-gravity-forms/
   */
  public function add_invoice_field( $field_groups ) {
    foreach( $field_groups as &$group ){
      if( $group["name"] == "pricing_fields" ){ 
        $group["fields"][] = array(
          "class"=>"button",
          "value" => __("Invoice", "gravityforms"),
          "onclick" => "StartAddField('invoice');"
        );
        break;
      }
    }
    return $field_groups;
  }

  public function invoice_title( $title, $field_type ) {
    if ( $field_type == 'invoice' ) {
      $title = __( 'Invoice', 'wp-proud-payment' );
    }
    return $title;
  }

  public function invoice_label() {
    //setting default field label
    ?>
    case "invoice" :
    field.label = "<?php _e( 'Invoice', 'wp-proud-payment' ) ?>";
    break;
    <?php
  }



  // Adds the input area to the external side
  public function invoice_field_input ( $input, $field, $value, $lead_id, $form_id ) {

    if ( $field['type'] != 'invoice' ) {
      return $input;
    }
    
    $value = is_array($value) ? $value : array( 'invoice' => $_GET['invoice'] ? $_GET['invoice'] : '', 'amount' => $_GET['amount'] ? $_GET['amount'] : '' );

    $unique_id = IS_ADMIN || $form_id == 0 ? "input_{$field['id']}" : 'input_' . $form_id . "_{$field['id']}";
    $input = "<span class=''>";
    $input .= "<input name='{$unique_id}_invoice' id='{$unique_id}_invoice' value='{$value[invoice]}' class='ginput_invoice' />";
    $input .= "<label for='{$unique_id}_invoice'>" . __( 'Invoice Number', 'wp-proud-payment' ) . "</label>";
    $input .= "</span>";

    $input .= "<span class=''>";
    $input .= "<input name='{$unique_id}_invoice' id='{$unique_id}_invoice' value='{$value[amount]}' class='ginput_amount' />";
    $input .= "<label for='{$unique_id}_invoice'>" . __( 'Amount', 'wp-proud-payment' ) . "</label>";
    $input .= "</span>";
    
    return '<div class="ginput_complex ginput_container no_prefix has_first_name no_middle_name has_last_name no_suffix gf_name_has_2 ginput_container_name">'. $input .'</div>';
  }

  // Now we execute some javascript technicalitites for the field to load correctly
  public function gform_editor_js(){
    ?>

    <script type='text/javascript'>

    jQuery(document).ready(function($) {
      //Add all textarea settings to the "TOS" field plus custom "invoice_setting"
      // fieldSettings["invoice"] = fieldSettings["textarea"] + ", .invoice_setting"; // this will show all fields that Paragraph Text field shows plus my custom setting

      // from forms.js; can add custom "invoice_setting" as well
      fieldSettings["invoice"] = ".label_setting, .description_setting, .admin_label_setting, .size_setting, .default_value_textarea_setting, .error_message_setting, .css_class_setting, .visibility_setting, .invoice_setting"; //this will show all the fields of the Paragraph Text field minus a couple that I didn't want to appear.

      //binding to the load field settings event to initialize the checkbox
      $(document).bind("gform_load_field_settings", function(event, field, form){
        jQuery("#field_invoice").attr("checked", field["field_invoice"] == true);
          $("#field_invoice_value").val(field["invoice"]);
        });
      });

    </script>
    <?php
  }

} // class

new ProudPayment;

// Payment meta box
class PaymentMeta extends \ProudMetaBox {

  public $options = [  // Meta options, key => default                             
    'type' => '',
    'link' => '',
    'form' => '',
    'icon' => '',
  ];

  public function __construct() {
    parent::__construct( 
      'payment', // key
      'Payment information', // title
      'payment', // screen
      'normal',  // position
      'high' // priority
    );
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {

    // Already set, no loading necessary
    if( $displaying ) {
      return;
    }

    $this->fields = [];

    // switch (get_option( 'payment_type', 'link' )) {
    //   case 'stripe':
    //     //$this->fields['key'] = 'Stripe product key';
    //     break;
    //   default: //case 'link'
        
    //     break;
    // }
    $this->fields['type'] = [
      '#type' => 'radios',
      '#title' => __('Type'),
      //'#description' => __('The type of search to fallback on when users don\'t find what they\'re looking for in the autosuggest search and make a full site search.', 'proud-settings'),
      '#options' => array(
        'gravityform' => __( 'Form' ),
        'link' => __( 'External link' ),
      ),
      
    ];

    $this->fields['link'] = [
      '#type' => 'text',
      '#title' => __('URL'),
      '#description' => __('Enter the full url to the payment page, including "https://"'),
      '#states' => [
        'visible' => [
          'type' => [
            'operator' => '==',
            'value' => ['link'],
            'glue' => '||'
          ],
        ],
      ],
    ];
  
    $this->fields['form'] = [
      '#type' => 'gravityform',
      '#title' => __('Form'),
      '#description' => __('Select a form. <a href="admin.php?page=gf_edit_forms" target="_blank">Create a new form</a>.'),
      '#states' => [
        'visible' => [
          'type' => [
            'operator' => '==',
            'value' => ['gravityform'],
            'glue' => '||'
          ],
        ],
      ],
    ];

    $this->fields['icon'] = [
      '#type' => 'fa-icon',
      '#title' => __('Icon'),
      '#description' => __('Selete the icon to use in the Actions app'),
    ];
  }
}
if( is_admin() )
  new PaymentMeta;

