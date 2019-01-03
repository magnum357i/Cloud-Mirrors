<?php

/**
 * To crate a new cloud API
 *
 * @package	 		Cloud Mirrors
 * @author     		Magnum357 [https://github.com/magnum357i/]
 * @copyright  		2019
 * @license    		http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace cloudmirrors\Helper;

class Builder {

	/**
	 * Email Domain
	 */
	protected $emailDomain = '';

	/**
	 * API URL
	 */
	public $serviceLink = 'https://webdav.yandex.com.tr/';

	/**
	 *
	 * @param 		$n 			Your Yandex Username
	 * @param 		$p 			Your Yandex Pasword
	 * @return 		void
	 */
	public function setLogin( $n, $p ) {

		$this->request()->setUser(
			( !empty( $this->emailDomain ) ) ? sprintf( "%s@%s", $n, $this->emailDomain ) : $n,
			$p
		);
	}

	/**
	 *
	 * @param 		$t 			Token you taken from Yandex
	 * @return 		void
	 */
	public function setToken( $t ) {

		$this->request()->setToken( $t );
	}

	/**
	 * Request Class
	 */
	protected $request = NULL;

	public function request() {

		if ( $this->request == NULL ) {

			$this->request = new \cloudmirrors\Helper\Request( $this->serviceLink );
		}

		return $this->request;
	}

	/**
	 *
	 * @param 		$bytes 			Bytes
	 * @return 		int
	 */
	protected function formatSize( $bytes ) {

		$size = sprintf( "%.0f", $bytes / pow( 1024, floor( ( strlen( $bytes ) - 1 ) / 3 ) ) );

		return $size;
	}
}