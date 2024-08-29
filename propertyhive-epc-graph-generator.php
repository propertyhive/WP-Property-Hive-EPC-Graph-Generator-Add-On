<?php
/**
 * Plugin Name: Property Hive EPC Graph Generator
 * Plugin Uri: http://wp-property-hive.com/addons/epc-graph-generator/
 * Description: Add an EPC graph generator to the EPCs section of a property record 
 * Version: 1.0.3
 * Author: PropertyHive
 * Author URI: http://wp-property-hive.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_EPC_Graph_Generator' ) ) :

final class PH_EPC_Graph_Generator {

    /**
     * @var string
     */
    public $version = '1.0.3';

    /**
     * @var Property Hive The single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main Property Hive EPC Graph Generator Instance
     *
     * Ensures only one instance of Property Hive EPC Graph Generator is loaded or can be loaded.
     *
     * @static
     * @return Property Hive EPC Graph Generator - Main instance
     */
    public static function instance() 
    {
        if ( is_null( self::$_instance ) ) 
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

        add_action( 'admin_notices', array( $this, 'epc_graph_generator_error_notices') );

        add_action( 'admin_enqueue_scripts', array( $this, 'load_epc_graph_generator_scripts' ) );

        add_action( 'propertyhive_property_epcs_fields', array( $this, 'epc_generator_meta_box' ) );

        add_action( 'wp_ajax_propertyhive_generate_epc_graph', array( $this, 'ajax_propertyhive_generate_epc_graph' ) );
    }

    /**
     * Define PH EPC Graph Generator Constants
     */
    private function define_constants() 
    {
        define( 'PH_EPC_GRAPH_GENERATOR_PLUGIN_FILE', __FILE__ );
        define( 'PH_EPC_GRAPH_GENERATOR_VERSION', $this->version );
    }

    private function includes()
    {
        //include_once( dirname( __FILE__ ) . "/includes/class-ph-map-search-install.php" );
    }

    /**
     * Output error message if core Property Hive plugin isn't active
     */
    public function epc_graph_generator_error_notices() 
    {
        if (!is_plugin_active('propertyhive/propertyhive.php'))
        {
            $message = __( "The Property Hive plugin must be installed and activated before you can use the Property Hive EPC Graph Generator add-on", 'propertyhive' );
            echo"<div class=\"error\"> <p>$message</p></div>";
        }
    }

    public function load_epc_graph_generator_scripts() 
    {
        global $pagenow, $post;

        if ( $pagenow != 'post-new.php' && isset($post->ID) && get_post_type($post->ID) == 'property' )
        {
            $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

            wp_register_script( 
                'ph-epc-graph-generator', 
                $assets_path . 'js/propertyhive-epc-graph-generator.js', 
                array(), 
                PH_EPC_GRAPH_GENERATOR_VERSION,
                true
            );

            wp_enqueue_script('ph-epc-graph-generator');

            // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            wp_localize_script( 'ph-epc-graph-generator', 'ph_epc_graph_generator_ajax_object', array( 
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'post_id' => $post->ID,
            ) );
        }
    }

    public function epc_generator_meta_box()
    {
        if ( get_option('propertyhive_epcs_stored_as', '') == 'urls' )
        {
            return;
        }
?>

<hr>

<div style="float:left; width:50%; max-width:300px;">
    <p class="form-field eer_current_field ">
        <label for="eer_current">EER Current (1-100)</label>
        <input type="number" class="short" name="eer_current" id="eer_current" min="1" max="100" value="" placeholder=""> 
    </p>

    <p class="form-field eer_potential_field ">
        <label for="eer_potential">EER Potential (1-100)</label>
        <input type="number" class="short" name="eer_potential" id="eer_potential" min="1" max="100" value="" placeholder=""> 
    </p>
</div>

<div style="float:left; width:50%; max-width:300px;">
    <p class="form-field eir_current_field ">
        <label for="eir_current">EIR Current (1-100)</label>
        <input type="number" class="short" name="eir_current" id="eir_current" min="1" max="100" value="" placeholder=""> 
    </p>

    <p class="form-field eir_potential_field ">
        <label for="eir_potential">EIR Potential (1-100)</label>
        <input type="number" class="short" name="eir_potential" id="eir_potential" min="1" max="100" value="" placeholder=""> 
    </p>
</div>
<div style="clear:both"></div>

<a href="" class="button button-primary generate-epc">Generate EPC</a>

<?php
    }

    private function get_y($value, $min, $max, $min_y, $max_y)
    {
        $value_diff = $max - $min;

        $y = (($value - $min) / $value_diff) * 100;

        // convert percentage back into px
        $px_diff = $max_y - $min_y;

        $y = ($y / 100) * $px_diff;

        return floor($y);
    }

    private function convert_value_to_image_and_y($value)
    {
        $value = (int)$value;
        $image = '1-20.png';
        $y = 100;
        if ( $value >= 1 && $value <= 20 )
        {
            $min_y = 275;
            $max_y = 303;

            $y = $max_y - $this->get_y($value, 1, 20, $min_y, $max_y);

            $image = '1-20.png';
        }
        if ( $value >= 21 && $value <= 38 )
        {
            $min_y = 243;
            $max_y = 271;

            $y = $max_y - $this->get_y($value, 21, 38, $min_y, $max_y);

            $image = '21-38.png';
        }
        if ( $value >= 39 && $value <= 54 )
        {
            $min_y = 211;
            $max_y = 239;

            $y = $max_y - $this->get_y($value, 39, 54, $min_y, $max_y);

            $image = '39-54.png';
        }
        if ( $value >= 55 && $value <= 68 )
        {
            $min_y = 179;
            $max_y = 207;

            $y = $max_y - $this->get_y($value, 55, 68, $min_y, $max_y);

            $image = '55-68.png';
        }
        if ( $value >= 69 && $value <= 80 )
        {
            $min_y = 147;
            $max_y = 175;

            $y = $max_y - $this->get_y($value, 69, 80, $min_y, $max_y);

            $image = '69-80.png';
        }
        if ( $value >= 81 && $value <= 91 )
        {
            $min_y = 115;
            $max_y = 143;

            $y = $max_y - $this->get_y($value, 81, 91, $min_y, $max_y);

            $image = '81-91.png';
        }
        if ( $value >= 92 && $value <= 100 )
        {
            $min_y = 83;
            $max_y = 111;

            $y = $max_y - $this->get_y($value, 92, 100, $min_y, $max_y);

            $image = '92-100.png';
        }

        return array($image, $y);
    }

    public function ajax_propertyhive_generate_epc_graph()
    {
        // Determine which EPC graph template to use
        // If ratings for only one of the ratings have been entered, we'll exclude the other type from the graph
        $epc_type = '';
        if ( $_POST['eer_current'] != '' && $_POST['eer_potential'] != '' )
        {
            if ( $_POST['eir_current'] != '' && $_POST['eir_potential'] != '' )
            {
                $background = dirname(__FILE__) . '/assets/images/background.png';
                $epc_type = 'eer_eir';
            }
            else
            {
                $background = dirname(__FILE__) . '/assets/images/background_eer.png';
                $epc_type = 'eer_only';
            }
        }
        elseif( $_POST['eir_current'] != '' && $_POST['eir_potential'] != '' )
        {
            $background = dirname(__FILE__) . '/assets/images/background_eir.png';
            $epc_type = 'eir_only';
        }
        else
        {
            header("Content-type:application/json");
            echo json_encode(array(
                'success' => false,
                'error' => 'Please ensure values are present for Current and Potential ratings',
            ));
        }

        $background = imagecreatefrompng($background);

        // If EER ratings are input, get the correct coloured pointer images and their vertical positions
        if ( in_array($epc_type, array('eer_eir', 'eer_only')) )
        {
            list($eer_current_image, $eer_current_y) = $this->convert_value_to_image_and_y($_POST['eer_current']);
            $eer_current = dirname(__FILE__) . '/assets/images/eer/' . $eer_current_image;
            $eer_current = imagecreatefrompng($eer_current);

            list($eer_potential_image, $eer_potential_y) = $this->convert_value_to_image_and_y($_POST['eer_potential']);
            $eer_potential = dirname(__FILE__) . '/assets/images/eer/' . $eer_potential_image;
            $eer_potential = imagecreatefrompng($eer_potential);
        }

        // If EIR ratings are input, get the correct coloured pointer images and their vertical positions
        if ( in_array($epc_type, array('eer_eir', 'eir_only')) )
        {
            list($eir_current_image, $eir_current_y) = $this->convert_value_to_image_and_y($_POST['eir_current']);
            $eir_current = dirname(__FILE__) . '/assets/images/eir/' . $eir_current_image;
            $eir_current = imagecreatefrompng($eir_current);

            list($eir_potential_image, $eir_potential_y) = $this->convert_value_to_image_and_y($_POST['eir_potential']);
            $eir_potential = dirname(__FILE__) . '/assets/images/eir/' . $eir_potential_image;
            $eir_potential = imagecreatefrompng($eir_potential);
        }

        // Create the background image of the image
        $background_image_width = $epc_type == 'eer_eir' ? 957 : 471;
        $output_image = imagecreatetruecolor($background_image_width, 404);
        imagecopyresized($output_image, $background, 0, 0, 0, 0, $background_image_width, 404, $background_image_width, 404);

        // Place the EER pointers in the correct place on the EPC graph background
        if ( in_array($epc_type, array('eer_eir', 'eer_only')) )
        {
            imagecopyresized($output_image, $eer_current, 313, $eer_current_y, 0, 0, 71, 31, 71, 31);
            imagecopyresized($output_image, $eer_potential, 390, $eer_potential_y, 0, 0, 71, 31, 71, 31);
        }

        // Place the EIR pointers in the correct place on the EPC graph background
        if ( in_array($epc_type, array('eer_eir', 'eir_only')) )
        {
            $eir_current_x = $epc_type == 'eer_eir' ? 801 : 316;
            $eir_potential_x = $epc_type == 'eer_eir' ? 877 : 393;
            imagecopyresized($output_image, $eir_current, $eir_current_x, $eir_current_y, 0, 0, 71, 31, 71, 31);
            imagecopyresized($output_image, $eir_potential, $eir_potential_x, $eir_potential_y, 0, 0, 71, 31, 71, 31);
        }

        // Add the rating text to the pointers in the correct position
        $white = imagecolorallocate($output_image, 255, 255, 255);
        $font = dirname(__FILE__) . '/assets/fonts/arial.ttf';

        if ( in_array($epc_type, array('eer_eir', 'eer_only')) )
        {
            $text = $_POST['eer_current'];
            $x = 346;
            if ( $_POST['eer_current'] > 9 ) { $x = $x-5; }
            imagettftext($output_image, 15, 0, $x, $eer_current_y + 23, $white, $font, $text);

            $text = $_POST['eer_potential'];
            $x = 423;
            if ( $_POST['eer_potential'] > 9 ) { $x = $x-5; }
            imagettftext($output_image, 15, 0, $x, $eer_potential_y + 23, $white, $font, $text);
        }

        if ( in_array($epc_type, array('eer_eir', 'eir_only')) )
        {
            $text = $_POST['eir_current'];
            $x = $epc_type == 'eer_eir' ? 834 : 349;
            if ( $_POST['eir_current'] > 9 ) { $x = $x-5; }
            imagettftext($output_image, 15, 0, $x, $eir_current_y + 23, $white, $font, $text);

            $text = $_POST['eir_potential'];
            $x = $epc_type == 'eer_eir' ? 910 : 426;
            if ( $_POST['eir_potential'] > 9 ) { $x = $x-5; }
            imagettftext($output_image, 15, 0, $x, $eir_potential_y + 23, $white, $font, $text);
        }

        $tmpfname = tempnam(sys_get_temp_dir(), 'ph_epc');

        imagepng($output_image, $tmpfname);

        $upload = wp_upload_bits('epc-' . $_POST['post_id'] . '-' . time() . '.png', null, file_get_contents($tmpfname));  
                                            
        if( isset($upload['error']) && $upload['error'] !== FALSE )
        {
            header("Content-type:application/json");
            echo json_encode(array(
                'success' => false,
                'error' => print_r($upload['error'], TRUE),
            ));
        }
        else
        {
            // We don't already have a thumbnail and we're presented with an image
            $wp_filetype = wp_check_filetype( $upload['file'], null );
        
            $attachment = array(
                 //'guid' => $wp_upload_dir['url'] . '/' . trim($media_file_name, '_'), 
                 'post_mime_type' => $wp_filetype['type'],
                 'post_title' => 'EPC',
                 'post_content' => '',
                 'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment( $attachment, $upload['file'], $_POST['post_id'] );
            
            if ( $attach_id === FALSE || $attach_id == 0 )
            {    
                header("Content-type:application/json");
                echo json_encode(array(
                    'success' => false,
                    'error' => 'Failed inserting image attachment ' . $upload['file'] . ' - ' . print_r($attachment, TRUE),
                ));
            }
            else
            {  
                $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                
                header("Content-type:application/json");
                echo json_encode(array(
                    'success' => true,
                    'url' => wp_get_attachment_url($attach_id),
                    'attachment_id' => $attach_id,
                ));
            }
        }

        unlink($tmpfname);

        die();
    }
}

endif;

/**
 * Returns the main instance of PH_EPC_Graph_Generator to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return PH_EPC_Graph_Generator
 */
function PHEPCGG() {
    return PH_EPC_Graph_Generator::instance();
}

PHEPCGG();

if( is_admin() && file_exists(  dirname( __FILE__ ) . '/propertyhive-epc-graph-generator-update.php' ) )
{
    include_once( dirname( __FILE__ ) . '/propertyhive-epc-graph-generator-update.php' );
}