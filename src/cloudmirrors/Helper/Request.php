<?php

/**
 * Request Controller
 *
 * @package	 		Cloud Mirrors
 * @author     		Magnum357 [https://github.com/magnum357i/]
 * @copyright  		2019
 * @license    		http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace cloudmirrors\Helper;

class Request {

	/**
	 * Site Link
	 */
	protected $siteLink = '';

	/**
	 * Token or login
	 */
	protected $accessMethod = '';

	/**
	 * Auth Info
	 */
	public static $login = [
		'username' => NULL,
		'password' => NULL
	];

	/**
	 * Token ID
	 */
	public static $token = '';

	/**
	 * Response Data
	 */
	protected $response = [
		'content' => '',
		'info'    => ''
	];

	/**
	 *
	 * @param 		$s 			Site Link
	 * @return 		void
	 */
	public function __construct( $s ) {

		$this->siteLink = $s;
	}

	/**
	 *
	 * @param 		$n 			Username
	 * @param 		$p 			Password
	 * @return 		void
	 */
	public function setUser( $n, $p ) {

		static::$login[ 'username' ] = $n;
		static::$login[ 'password' ] = $p;

		$this->accessMethod = 'login';
	}

	/**
	 *
	 * @param 		$t 			Token ID
	 * @return 		void
	 */
	public function setToken( $t ) {

		static::$token = $t;

		$this->accessMethod = 'token';
	}

	/**
	 *
	 * @param 		$command 				API Command
	 * @param 		$url 					Request URL
	 * @param 		$customHeaders 			Headers for cURL
	 * @param 		$customOptions 			Options for cURL
	 * @return 		void
	 */
	public function send( $command, $url='', $customHeaders=[], $customOptions=[] ) {

		$cSession = curl_init();
		$headers  = [];
		$options  = [];

		if ( $this->accessMethod == 'login' ) {

			$options[ CURLOPT_HTTPAUTH ] = CURLAUTH_ANY;
			$options[ CURLOPT_USERPWD ]  = implode( ':', static::$login );
		}

		$options[ CURLOPT_URL ]            = $this->createUrl( $url );
		$options[ CURLOPT_SSL_VERIFYPEER ] = FALSE;
		$options[ CURLOPT_SSL_VERIFYHOST ] = FALSE;
		$options[ CURLOPT_CUSTOMREQUEST ]  = $command;
		$options[ CURLOPT_HEADER ]         = FALSE;
		$options[ CURLOPT_RETURNTRANSFER ] = TRUE;

		foreach ( $customOptions as $o ) {

			$options[ $o[ 0 ] ] = $o[ 1 ];
		}

		if ( $this->accessMethod == 'token' ) {

			$headers[] = sprintf( "Authorization: OAuth %s", static::$token );
		}

		foreach ( $customHeaders as $h ) {

			$headers[] = sprintf( "%s: %s", $h[ 0 ], $h[ 1 ] );
		}

		curl_setopt_array( $cSession, $options );
		curl_setopt(       $cSession, CURLOPT_HTTPHEADER, $headers );

        $this->response[ 'content' ] = curl_exec( $cSession );
        $this->response[ 'info' ]    = curl_getinfo( $cSession );

		curl_close( $cSession );
	}

	/**
	 *
	 * @return 		string
	 */
	public function getContent() {

		return $this->response[ 'content' ];
	}

	/**
	 *
	 * @return 		int
	 */
	public function getStatus() {

		return $this->response[ 'info' ][ 'http_code' ];
	}

	/**
	 *
	 * @return 		array
	 */
	public function getInfo() {

		return $this->response[ 'info' ];
	}

	/**
	 *
	 * @param 		$url 			Request URL
	 * @return 		array
	 */
	protected function createUrl( $url ) {

		return $this->siteLink . urlencode( $url );
	}
}