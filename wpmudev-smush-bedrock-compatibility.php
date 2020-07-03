<?php
/**
  * Plugin Name: Smush Pro - Bedrock CDN compatibility
  * Plugin URI: https://premium.wpmudev.org/
  * Description: Add CDN compatibility to bedrock installations (as of 3.6.2)
  * Author: Panos Lyrakis, Alessandro Kaounas @ WPMUDEV
  * Author URI: https://premium.wpmudev.org/
  * Task: SLS-116
  * License: GPLv2 or later
  */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// No need to do anything if the request is via WP-CLI.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	return;
}


if ( ! class_exists( 'WPMUDEV_Smush_CDN_Compatibility' ) ) {
    
    class WPMUDEV_Smush_CDN_Compatibility {

        private static $_instance = null;

        public static function get_instance() {

            if( is_null( self::$_instance ) ){
                self::$_instance = new WPMUDEV_Smush_CDN_Compatibility();
            }

            return self::$_instance;
            
        }

        private function __construct() {
            $this->init();
        }

        public function init(){

            if( ! method_exists( '\Smush\Core\Settings', 'get_instance' ) || ! defined( 'WP_SMUSH_VERSION' ) || WP_SMUSH_VERSION < '3.6' ) {
                return;
            }

            add_action( 'template_redirect', array( $this, 'wpmudev_serve_images' ) );
            add_action( 'template_redirect', array( $this, 'wpmudev_replace_cdn_urls' ), 0 );

        }

        public function wpmudev_serve_images(){

            if( is_404() || ! is_404() ) {

                if( isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp/uploads/' ) !== false ){

                    // Serve file method
                    $image = str_replace( '/app', '', WP_CONTENT_DIR ) . str_replace( '/wp/uploads/', '/app/uploads/', $_SERVER['REQUEST_URI'] );

                    if( ! file_exists( $image ) ){
                        return;
                    }
                    
                    $info = getimagesize( $image );
                    
                    if( empty( $info ) ){
                        return;
                    }
        
                    status_header( 200 );
                    header( "Content-type: " . $info['mime'] );
                    header( "Content-length: " . (int) filesize( $image ) );
                    readfile( $image );
                    exit;
        
                }

            }
        
        }

        public function wpmudev_replace_cdn_urls(){
            ob_start( array( $this, 'wpmudev_parser_helper' ) );
        }

        public function wpmudev_parser_helper( $content ){
//
            return str_replace( $this->smush_cdn_base() . '-content/uploads/', $this->smush_cdn_base() . 'uploads/', $content );
        }

        private function smush_cdn_base(){
            
            $settings = \Smush\Core\Settings::get_instance()->get_setting( WP_SMUSH_PREFIX . 'cdn_status' );

            $site_id = absint( $settings->site_id );
        
            return trailingslashit( "https://{$settings->endpoint_url}/{$site_id}" );

        }

    }

    add_action( 'plugins_loaded', function(){
        return WPMUDEV_Smush_CDN_Compatibility::get_instance();
    });

}
