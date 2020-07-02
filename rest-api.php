<?php
/**
 * An example add-on for WP Job Openings plugin to expose application form and other fields to WP REST API.
 *
 * @package wp-job-openings
 */

/**
 * Plugin Name: WP Job Openings REST API
 * Plugin URI: https://wordpress.org/plugins/wp-job-openings/
 * Description: Handle REST APIs for WP Job Openings plugin.
 * Author: AWSM Innovations
 * Author URI: https://awsm.in/
 * Version: 0.0.1
 * Licence: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Constants
if ( ! defined( 'AWSM_JOBS_REST_PLUGIN_BASENAME' ) ) {
	define( 'AWSM_JOBS_REST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'AWSM_JOBS_REST_PLUGIN_DIR' ) ) {
	define( 'AWSM_JOBS_REST_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}
if ( ! defined( 'AWSM_JOBS_REST_PLUGIN_URL' ) ) {
	define( 'AWSM_JOBS_REST_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
}
if ( ! defined( 'AWSM_JOBS_REST_PLUGIN_VERSION' ) ) {
	define( 'AWSM_JOBS_REST_PLUGIN_VERSION', '0.0.1' );
}

class AWSM_Jobs_REST_API_Example {
	private static $instance = null;

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function run() {
		if ( class_exists( 'AWSM_Job_Openings' ) ) {
			add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

			add_filter( 'rest_awsm_job_openings_collection_params', array( $this, 'rest_collection_params' ) );
			add_filter( 'rest_prepare_awsm_job_openings', array( $this, 'rest_prepare' ), 10, 2 );
			add_filter( 'allowed_http_origins', array( $this, 'allowed_http_origins' ) );
		}
	}

	public function rest_api_init() {
		register_rest_field( 'awsm_job_openings', '_job_specs', array(
			'get_callback' => array( $this, 'get_specs' ),
		) );
	}

	public function get_specs( $job_data ) {
		$listing_specs  = get_option( 'awsm_jobs_listing_specs' );
		return AWSM_Job_Openings::get_specifications_content( $job_data['id'], false, array(), array( 'specs' => $listing_specs ) );
	}

	public function rest_collection_params( $query_params ) {
		$query_params['status']['default'] = array( 'publish', 'expired' );
		$query_params['status']['sanitize_callback'] = '';
		return $query_params;
	}

	public function rest_prepare( $response, $post ) {
		$res_data = $response->get_data();
		$content = $post->post_content;
		ob_start();
		include AWSM_Job_Openings::get_template_path( 'job-content.php' );
		$job_content = ob_get_clean();
		$res_data['content']['rendered'] = $job_content;
		$response->set_data( $res_data );
		return $response;
	}

	public function allowed_http_origins( $origins ) {
		if ( defined( 'AWSM_JOBS_REST_IS_LOCAL' ) && AWSM_JOBS_REST_IS_LOCAL ) {
			$origins[] = 'http://localhost:3000';
		}
		return $origins;
	}
}

$awsm_jobs_rest_api = AWSM_Jobs_REST_API_Example::init();

add_action( 'plugins_loaded', array( $awsm_jobs_rest_api, 'run' ) );
