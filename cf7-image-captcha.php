<?php
/**
 * Plugin Name:       Contact Form 7 Image Captcha
 * Plugin URI:        https://wordpress.org/plugins/contact-form-7-image-captcha/
 * Description:       Add a simple image captcha and Honeypot to contact form 7
 * Version:           1.1
 * Author:            Kyle Charlton
 * Author URI:        https://profiles.wordpress.org/ktc_88
 * License:           GNU General Public License v2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cf7-image-captcha
 */

add_action('plugins_loaded', 'cf7ic_load_textdomain');
function cf7ic_load_textdomain() {
    load_plugin_textdomain( 'cf7-image-captcha', false, dirname( plugin_basename(__FILE__) ) . '/lang' );
}

// register style on initialization
add_action('init', 'cf7ic_register_style');
function cf7ic_register_style() {
    wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css' );
    wp_enqueue_style( 'cf7ic_style', plugins_url('/style.css', __FILE__), false, '1.0.0', 'all');
}

function cf7ic_check_if_spam( $result, $tag ) {
    $type = $tag['type'];
    $name = $tag['name'];
    $value = $_POST[$name] ;
    
    // Allow Contact Forms without [cf7ic] to send
    if($_POST['cf7ic_exists']) {
	if(!empty($_POST['kc_honeypot']) || $_POST['kc_captcha'] != "kc_human" ) {
		$result['valid'] = false;
		$result['reason'] = array( $name => wpcf7_get_message( 'spam' ) );
	} 
	return $result;
    }
    
    // Allow Contact Forms without [cf7ic] to send
    if($_POST['cf7ic_exists'] != "true") {
        return $result;
    }
}
add_filter('wpcf7_validate_text','cf7ic_check_if_spam', 10, 2);
add_filter('wpcf7_validate_text*','cf7ic_check_if_spam', 10, 2 );
add_filter('wpcf7_validate_textarea', 'cf7ic_check_if_spam', 10, 2);
add_filter('wpcf7_validate_textarea*', 'cf7ic_check_if_spam', 10, 2);

// RESOURCE HELP
// http://stackoverflow.com/questions/17541614/use-thumbnail-image-instead-of-radio-button    
// http://jsbin.com/pafifi/1/edit?html,css,output   
// http://jsbin.com/nenarugiwe/1/edit?html,css,output

// Allow shortcodes in contact form 7's form builder
add_filter( 'wpcf7_form_elements', 'do_shortcode' );

function CF7IC_Function( $args ){
    
    // Adds an argument to the shortcode to record the type of form (Contact us, Request a Visit, Refer a Friend...) - [fuel-spam-guard form="Contact Us"]
    extract( shortcode_atts( array( 'form' => '' ), $args ) );
    
    // Create an array to hold the image library
    $captchas = array(
        __( 'Heart', 'cf7-image-captcha') => "fa-heart", 
        __( 'House', 'cf7-image-captcha') => "fa-home", 
        __( 'Star', 'cf7-image-captcha')  => "fa-star", 
        __( 'Car', 'cf7-image-captcha')   => "fa-car", 
        __( 'Cup', 'cf7-image-captcha')   => "fa-coffee", 
        __( 'Flag', 'cf7-image-captcha')  => "fa-flag", 
        __( 'Key', 'cf7-image-captcha')   => "fa-key", 
        __( 'Truck', 'cf7-image-captcha') => "fa-truck", 
        __( 'Tree', 'cf7-image-captcha')  => "fa-tree", 
        __( 'Plane', 'cf7-image-captcha') => "fa-plane"
    );

    $choice = array_rand( $captchas, 3);
    foreach($choice as $key) {
        $choices[$key] = $captchas[$key];
    }
    
    // Pick a number between 0-2 and use it to determine which array item will be used as the answer
    $human = rand(0,2);
    
    ob_start(); ?>
    
        <div class="captcha-image">

            <p><?php _e('Please prove you are human by selecting the', 'cf7-image-captcha'); ?> <span><?php echo $choice[$human]; ?></span></p>
            
            <?php
            $i = -1;
            foreach($choices as $title => $image) {
                $i++;
                if($i == $human) { $value = "kc_human"; } else { $value = "bot"; };
                echo  '<label><input type="radio" name="kc_captcha" value="'. $value .'"/><i class="fa '. $image .'"></i></label>';
            }
            ?>
            
            <!--label><input type="radio" name="captcha" value="human" /><i class="fa fa-heart"></i></label> <label><input type="radio" name="captcha" value="bot"  /><i class="fa fa-home"></i></label> <label><input type="radio" name="captcha" value="bot" /><i class="fa fa-star"></i></label-->
        </div>
        <div style="display:none">
            <input type="text" name="kc_honeypot">
            <input type="hidden" name="FormType" value="<?php echo $form ?>"/>
            <input type="hidden" name="cf7ic_exists" value="true"/>
        </div>

    <?php    // more code
    $result = ob_get_contents(); // get everything in to $result variable
    ob_end_clean();
    return $result;
}
add_shortcode('cf7ic', 'CF7IC_Function');
