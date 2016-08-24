<?php
/**
 * Plugin Name: EDD Dev/Test Licensing
 * Plugin URL: http://easydigitaldownloads.com/extension/software-licenses
 * Description: Add-on to EDD Software Licenses - lets commercial plugins be activated and updated on local/dev/test/staging with single license.
 * Version: 1.0
 * Author: Cimbura.com
 * Author URI: http://cimbura.com
 */


class EDD_DevTest_Licensing {

	private static $instance = null;
	private $edd_settings = array();

	private function __construct() {}

	public static function get_instance() {
		if ( ! self::$instance ) {
			$class = __CLASS__;
			self::$instance = new $class();
		}
		return self::$instance;
	}

	public function hook() {
		add_filter( 'edd_settings_extensions', array( $this, 'add_settings' ), 11 ); // Run after EDD Software Licensing
		add_filter( 'edd_sl_url_tlds', array( $this, 'add_tlds' ) );
		add_filter( 'edd_sl_url_subdomains', array( $this, 'add_subdomains' ) );
		add_filter( 'edd_sl_is_local_url', array( $this, 'check_host_by_name' ), 10, 2 );
	}

	// Override option edd_sl_bypass_local_hosts.
	public function add_settings( $settings ) {
		if ( empty( $settings['software-licensing'] ) )
			return $settings;

		$edd_settings = $this->get_settings();

		if ( empty( $edd_settings['edd_sl_bypass_local_hosts'] ) || ! $edd_settings['edd_sl_bypass_local_hosts'] )
			return $settings;

		$sl_settings = $settings['software-licensing'];
		foreach ( $sl_settings as $idx => $setting ) {
			if ( $setting['id'] == 'edd_sl_bypass_local_hosts' ) {
				$second_part = array_splice( $sl_settings, $idx + 1 );
				//Add our stuff.
				$sl_settings[] = array(
					'id'   => 'edd_sl_bypass_tlds',
					'name' => __( 'Ignore domains and TLDs', 'edd_sl' ),
					'desc' => __( 'Enter domains and TLDs (one per line) for additional development domains (.dev & .local automatically included).', 'edd_sl' ),
					'type' => 'textarea',
					'std'  => implode( "\r\n", $this->get_extra_tlds() ),
				);
				$sl_settings[] = array(
					'id'   => 'edd_sl_bypass_subdomains',
					'name' => __( 'Ignore subdomains', 'edd_sl' ),
					'desc' => __( 'Enter subdomains (one per line) for additional development domains (dev. and staging. automatically included).', 'edd_sl' ),
					'type' => 'textarea',
					'std'  => implode( "\r\n", $this->get_extra_subdomains() ),
				);
				$settings['software-licensing'] = array_merge( $sl_settings, $second_part );
			}
		}
		return $settings;
	}

	public function add_tlds( $tlds ) {
		$edd_settings = $this->get_settings();
		$extra_tlds = isset( $edd_settings['edd_sl_bypass_tlds'] ) ? explode( "\r\n", $edd_settings['edd_sl_bypass_tlds'] ) : $this->get_extra_tlds();
		return array_merge( $tlds, $extra_tlds );
	}

	public function add_subdomains( $subdomains ) {
		$edd_settings = $this->get_settings();
		$extra_subdomains = isset( $edd_settings['edd_sl_bypass_subdomains'] ) ?  explode( "\r\n", $edd_settings['edd_sl_bypass_subdomains'] ) : $this->get_extra_subdomains();
		return $subdomains;
	}

	public function check_host_by_name( $is_local_url, $url ) {
		if ( $is_local_url )
			return $is_local_url;

		// Try to get the IP.
		$url_parts = parse_url( $url );
		$host = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

		if ( ip2long( $host ) == false ) {
			// Not an IP, try gethostbyname().
			$ip = gethostbyname( $host );
			if ( $ip != $host && $this->is_private_ip( $ip ) ) {
				return true;
			}
		}

		// Last ditch effort: look at the REMOTE_ADDR
		if ( $this->is_private_ip( $_SERVER['REMOTE_ADDR'] ) ) {
			return true;
		}

		return $is_local_url;
	}

	private function get_settings() {
		if ( empty( $this->edd_settings ) ) {
			$this->edd_settings = edd_get_settings();
		}
		return $this->edd_settings;
	}

	private function is_private_ip( $ip ) {
		return ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Domains .dev & .local automatically included by EDD Software Licensing.
	 */
	private function get_extra_tlds() {
		return array(
			'.lan',
			'.mac',
			'.han',
			'.gridserver.com',
			'.lightningbasehosted.com',
			'.pantheonsite.io',
			'.wpengine.com',
			'.wsynth.net',
			'.xip.io',
		);
	}

	/**
	 * Subdomains dev. and staging. automatically included by EDD Software Licensing.
	 */
	private function get_extra_subdomains() {
		return array(
			'stage.',
			'test.',
			'new.',
		);
	}
}

EDD_DevTest_Licensing::get_instance()->hook();



