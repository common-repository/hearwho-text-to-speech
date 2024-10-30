<?php
/**
 * Plugin Name: HearWho Text-to-speech
 * Plugin URI: https://hearwho.com/Home/WordPress
 * Description: Add text to speech automatically for your blog. Allow users to listen instead of read to entries. This is useful in the car, on the go, and to help your blog more accessible for your users. Simply activate the plugin and enter in your user key on the hearwho plugin settings page. You can get a free key for your blog by visiting www.hearwho.com .
 * Version: 1.0
 * Author: Derick Ariyam and Rick Farnsworth
 */

add_action( 'the_content', 'hearwho_append_listen_to_link' );

 	function hearwho_append_listen_to_link ( $content ) {

	try
	{

		$options = get_option( 'HearWho_settings' );
		$apiKey = $options['HearWho_API_KEY'];

		$speakerIcon = plugin_dir_url( __FILE__ ) . 'speaker-icon.png';


		 $hearwho_url= get_post_meta( get_the_ID(), 'hearwho_url',true);
		 $hearwho_listenToThis = '<img src="' . $speakerIcon . '"> <a target="_blank" href="#" onclick="window.open(\'https://hearwho.com/home/play?id=' . $hearwho_url . '\', \'newwindow\', \'width=400,height=250\'); return false;">Listen to this text</a>';




		 if (empty($hearwho_url))
		 {
		   return $content;
		 }
		 else
		 {
		   if (wp_http_validate_url($hearwho_url))
		   {

			 return $hearwho_listenToThis . $content;
		   }
		   else
		   {

			return  "<p style='color:darkred'>HearWho Plugin Message: " .  $hearwho_url . "</p>" . $content;
		   }


		 }
	}
	catch (exception $e)
	{

		return $content;

	}



}


add_action('wp_enqueue_scripts', 'hearwho_styles');
function hearwho_styles() {
	wp_enqueue_script('jquery');
    $cssFile = plugin_dir_url( __FILE__ ) . 'hearwho.css';
    $jsFile = plugin_dir_url( __FILE__ ) . 'hearwho.js';
    wp_register_style( 'hearwhostyles', $cssFile );
    wp_enqueue_style( 'hearwhostyles' );
    wp_enqueue_script( 'hearwhoscripts', $jsFile);
}



add_action( 'save_post', 'HearWho_GetURL' );

function HearWho_GetURL($post_id) {
		$options = get_option( 'HearWho_settings' );
		$apiKey = $options['HearWho_API_KEY'];

		 // get the post
		  $the_post = get_post($post_ID);

		  // get the content of the post
  		 $post_content = $the_post->post_content;

  		  // get the title of the post
  		 $post_title = $the_post->post_title;

	     $body =   $post_title . '. ' . $post_content;
	  	 if (empty($post_content))
	  	 {

	  	 }
	  	 else
	  	 {
	  	 	     $url = send_to_hearwho($body,$apiKey);
		 		update_post_meta ($post_id, 'hearwho_url', $url);
	  	 }




}



 function send_to_hearwho ($content, $apiKey ) {
	$url = "https://hearwho.com/api/make/";

	$data = array(
			'UserToken' => $apiKey,
			'voice' => '1',
			'speed' => '1',
			'quality' => '2',
			'Content'   => $content
			);

	$options = array(
		'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded",
			'method'  => 'POST',
			'content' => http_build_query($data)
		)
	);

	$args = array(
    	'body'        => $data,
    	'timeout'     => '5',
    	'redirection' => '5',
    	'httpversion' => '1.0',
    	'blocking'    => true,
    	'headers'     => array(),
    	'cookies'     => array(),
	);


	//$context  = stream_context_create($options);
	//$resp = file_get_contents($url, false, $context);

	try
	{
		$response = wp_remote_post($url, $args);
		$resp = wp_remote_retrieve_body( $response );
 		return str_replace('"','',$resp);

	}
	catch (exception $ex)
	{
		return $ex;
	}



}


add_action( 'admin_menu', 'HearWho_add_admin_menu' );
add_action( 'admin_init', 'HearWho_settings_init' );


function HearWho_add_admin_menu(  ) {

	add_options_page( 'HearWho', 'HearWho', 'manage_options', 'hearwho', 'HearWho_options_page' );

}


function HearWho_settings_init(  ) {

	register_setting( 'pluginPage', 'HearWho_settings' );

	add_settings_section(
		'HearWho_pluginPage_section',
		__( 'HearWho API Key', 'API Key' ),
		'HearWho_settings_section_callback',
		'pluginPage'
	);

	add_settings_field(
		'HearWho_API_KEY',
		__( 'HearWho API Key', 'API Key' ),
		'HearWho_API_KEY_render',
		'pluginPage',
		'HearWho_pluginPage_section'
	);


}


function HearWho_API_KEY_render(  ) {

	$options = get_option( 'HearWho_settings' );
	?>
	<input type='text' name='HearWho_settings[HearWho_API_KEY]' value='<?php echo esc_attr($options['HearWho_API_KEY']); ?>'>
	<?php

}


function HearWho_settings_section_callback(  ) {

	echo __( 'Please enter your unique HearWho Api key. If you do not have a key, please visit www.HearWho.com and create an account for a free API Key. Your key will look like a long alpha-numeric string, something like: ceb877ec-1587-494f-ac4f-052b5b7989b8', 'hearwho-text-to-speech' );

}


function HearWho_options_page(  ) {

		?>
		<form action='options.php' method='post'>

			<h2>HearWho</h2>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

		</form>
		<?php

}

