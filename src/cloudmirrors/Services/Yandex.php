<?php

/**
 * Yandex Disk API
 *
 * @package	 		Cloud Mirrors
 * @author     		Magnum357 [https://github.com/magnum357i/]
 * @copyright  		2019
 * @license    		http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace cloudmirrors\Services;

class Yandex {

	/**
	 * API URL
	 */
	const serviceLink = 'https://cloud-api.yandex.net/v1/disk/';

	/**
	 *
	 * @param 		$n 			Your Yandex Username
	 * @param 		$p 			Your Yandex Pasword
	 * @return 		void
	 */
	public function setLogin( $n, $p ) {

		$this->request()->setUser( $n, $p );
	}

	/**
	 * Request Class
	 */
	protected $request = NULL;

	public function request() {

		if ( $this->request == NULL ) {

			$this->request = new \cloudmirrors\Helper\Request( static::serviceLink );
		}

		return $this->request;
	}

	/**
	 *
	 * @param 		$remoteFile 			File path on Yandex
	 * @param 		$localPath 				Path to save
	 * @return 		bool
	 */
	public function downloadFile( $remoteFile, $localPath ) {

		$remoteInfo = $this->splitter( $remoteFile );

		if ( $remoteInfo[ 'type' ] != 'file' ) throw new \Exception( "[Cloudmirrors Yandex Error] File must be enter" );

		$this->request()->send(
			'GET',
			$remoteFile
		);

		var_dump( $this->request()->getContent() ); die;

		if ( $this->request()->getStatus() != 200 ) throw new \Exception( "[Cloudmirrors Yandex Error] File not download" );

		file_put_contents( $localPath . $remoteInfo[ 'file' ][ 'name' ] . '.' . $remoteInfo[ 'file' ][ 'ext' ], $this->request()->getContent() );

		return TRUE;
	}

	/**
	 *
	 * @param 		$localFile 				Local file path
	 * @param 		$remotePath 			Path on Yandex
	 * @return 		bool
	 */
	public function uploadFile( $localFile, $remotePath ) {

		$fileHandler = fopen( $localFile, 'r' );

		if ( $fileHandler == NULL ) throw new \Exception( "[Cloudmirrors Yandex Error] File not exists" );

		$fileInfo = $this->splitter( $localFile );
		$fileSize = filesize( $localFile );

		$this->request()->send(
			'PUT',
			$remotePath . $fileInfo[ 'file' ][ 'name' ] . '.' . $fileInfo[ 'file' ][ 'ext' ],
			[
				[ 'Etag',           md5_file( $localFile ) ],
				[ 'Sha256',         hash_file( 'sha256', $localFile ) ],
				[ 'Content-Type',   mime_content_type( $localFile ) ],
				[ 'Content-Length', $fileSize ],
			],
			[
				[ CURLOPT_UPLOAD,     TRUE ],
				[ CURLOPT_INFILE,     $fileHandler ],
				[ CURLOPT_INFILESIZE, $fileSize ],
			]
		);

		fclose( $fileHandler );

		if ( $this->request()->getStatus() == 409 ) throw new \Exception( "[Cloudmirrors Yandex Error] Directory not exists" );

		return ( $this->request()->getStatus() == 201 ) ? TRUE : FALSE;
	}

	/**
	 *
	 * @param 		$remoteFile 			File on Yandex
	 * @return 		bool
	 */
	public function hasFile( $remoteFile ) {

		$this->request()->send(
			'GET',
			$remoteFile,
			[],
			[
				[ CURLOPT_NOBODY, TRUE ]
			]
		);

		return ( $this->request()->getStatus() == 200 ) ? TRUE : FALSE;
	}

	/**
	 *
	 * @param 		$remotePath 			Path on Yandex
	 * @return 		bool
	 */
	public function listDirectory( $remotePath ) {

		$this->request()->send(
			'PROPFIND',
			$remotePath,
			[
				[ 'Depth', '1' ]
			]
		);

		if ( $this->request()->getStatus() == 404 ) throw new \Exception( "[Cloudmirrors Yandex Error] Directory not exists" );

		preg_match_all( '@<d:response>(.*?)</d:response>@si', $this->request()->getContent(), $tags );

		$list = [ 'directory' => [], 'files' => [] ];
		$i    = 0;

		foreach ( $tags[ 1 ] as $tag ) {

			$values = [];

			preg_match( '@<d:getcontentlength>(.*?)</d:getcontentlength>@si', $tag, $val );

			if ( isset( $val[ 1 ] ) ) $values[ 'size' ] = $val[ 1 ];

			preg_match( '@<d:displayname>(.*?)</d:displayname>@si', $tag, $val );

			$values[ 'name' ] = $val[ 1 ];

			preg_match( '@<d:creationdate>(.*?)</d:creationdate>@si', $tag, $val );

			$values[ 'created' ] = $val[ 1 ];

			preg_match( '@<d:getlastmodified>(.*?)</d:getlastmodified>@si', $tag, $val );

			$values[ 'edited' ] = $val[ 1 ];

			preg_match( '@<d:getcontenttype>(.*?)</d:getcontenttype>@si', $tag, $val );

			if ( isset( $val[ 1 ] ) ) {

				$values[ 'info' ] = $val[ 1 ];
				$values[ 'type' ] = 'file';
			}
			else {

				$values[ 'type' ] = 'folder';
			}

			if ( $i == 0 ) {

				unset( $values[ 'type' ] );

				$list[ 'directory' ] = $values;
			}
			else {

				$list[ 'files' ][ $i ] = $values;
			}

			$i++;
		}

		return $list;
	}

	/**
	 *
	 * @param 		$remote 				File or folder on Yandex
	 * @param 		$targetPath 			Target path on Yandex
	 * @return 		bool
	 */
	public function move( $remote, $targetPath, $overwrite='F' ) {

		$remoteInfo     = $this->splitter( $remote );
		$targetPathInfo = $this->splitter( $targetPath );

		if ( $targetPathInfo[ 'type' ] != 'path' ) throw new \Exception( "[Cloudmirrors Yandex Error] Target is not a path" );

		$destination = $targetPath;

		if ( $remoteInfo[ 'type' ] == 'file' ) {

			$destination = $destination . '/' . $remoteInfo[ 'file' ][ 'name' ] . '.' . $remoteInfo[ 'file' ][ 'ext' ];
		}

		$destination = '/' . $destination;

		$this->request()->send(
			'MOVE',
			$remote,
			[
				[ 'Destination', $destination ],
				[ 'Overwrite',   $overwrite ]
			]
		);

		if ( $this->request()->getStatus() == 404 ) throw new \Exception( "[Cloudmirrors Yandex Error] File or folder not exists" );

		if ( $this->request()->getStatus() == 407 ) throw new \Exception( "[Cloudmirrors Yandex Error] Destination path is wrong" );

		if ( $this->request()->getStatus() == 409 ) throw new \Exception( "[Cloudmirrors Yandex Error] Destination and remote path is the same" );

		return ( $this->request()->getStatus() == 201 ) ? TRUE : FALSE;
	}

	/**
	 *
	 * @param 		$remote 			File or folder on Yandex
	 * @param 		$newName 			New name
	 * @param 		$overwrite 			Overwrite option
	 * @return 		bool
	 */
	public function rename( $remote, $newName, $overwrite='F' ) {

		$remoteInfo = $this->splitter( $remote );

		if ( $remoteInfo[ 'type' ] == 'path' ) {

			$remoteInfo[ 'path' ][ count( $remoteInfo[ 'path' ] ) - 1 ] = $newName;

			$destination = implode( '/', $remoteInfo[ 'path' ] ) . '/';
		}
		else if ( $remoteInfo[ 'type' ] == 'file' ) {

			$destination = implode( '/', $remoteInfo[ 'path' ] ) . '/' . $newName . '.' . $remoteInfo[ 'file' ][ 'ext' ];
		}

		$destination = '/' . $destination;

		$this->request()->send(
			'MOVE',
			$remote,
			[
				[ 'Destination', $destination ],
				[ 'Overwrite',   $overwrite ]
			]
		);

		if ( $this->request()->getStatus() == 404 ) throw new \Exception( "[Cloudmirrors Yandex Error] File not exists" );

		if ( $this->request()->getStatus() == 407 ) throw new \Exception( "[Cloudmirrors Yandex Error] Destination path is wrong" );

		if ( $this->request()->getStatus() == 409 ) throw new \Exception( "[Cloudmirrors Yandex Error] Destination and remote path is the same" );

		return ( $this->request()->getStatus() == 201 ) ? TRUE : FALSE;
	}

	/**
	 *
	 * @param 		$remote 			File or folder on Yandex
	 * @return 		bool
	 */
	public function delete( $remote ) {

		$this->request()->send(
			'DELETE',
			$remote
		);

		if ( $this->request()->getStatus() == 404 ) throw new \Exception( "[Cloudmirrors Yandex Error] File or folder not exists" );

		return ( in_array( $this->request()->getStatus(), [ 202, 204 ] ) ) ? TRUE : FALSE;
	}

	/**
	 *
	 * @param 		$remote 			Path on Yandex
	 * @return 		bool
	 */
	public function createFolder( $remotePath ) {

		$this->request()->send(
			'MKCOL',
			$remotePath
		);

		if ( $this->request()->getStatus() == 409 ) throw new \Exception( "[Cloudmirrors Yandex Error] Too many folders to not create" );

		if ( $this->request()->getStatus() == 405 ) throw new \Exception( "[Cloudmirrors Yandex Error] Folder already exists" );

		return ( $this->request()->getStatus() == 201 ) ? TRUE : FALSE;
	}

	/**
	 *
	 * @return 		array
	 */
	public function getDiskInfo() {

		$this->request()->setHeader( 'Depth', '0' );

		$xml  = '';
		$xml .= '<D:propfind xmlns:D="DAV:">';
		$xml .= '<D:prop>';
		$xml .= '<D:quota-available-bytes/>';
		$xml .= '<D:quota-used-bytes/>';
		$xml .= '</D:prop>';
		$xml .= '</D:propfind>';

		$this->request()->send(
			'PROPFIND',
			'',
			[
				[ 'Content-Type',   'application/xml; charset="utf-8"' ],
				[ 'Content-Length', strlen( $xml ) ]
			],
			[
				[ CURLOPT_POSTFIELDS, $xml ]
			]
		);

		$return = [ 'used' => 0, 'available' => 0 ];

		preg_match( '@<d:quota-used-bytes>(.*?)</d:quota-used-bytes>@si', $this->request()->getContent(), $out );

		if ( isset( $out[ 1 ] ) ) $return[ 'used' ] = $this->formatSize( $out[ 1 ] );

		preg_match( '@<d:quota-available-bytes>(.*?)</d:quota-available-bytes>@si', $this->request()->getContent(), $out );

		if ( isset( $out[ 1 ] ) ) $return[ 'available' ] = $this->formatSize( $out[ 1 ] );

		return $return;
	}

	/**
	 *
	 * @param 		$remote 			File or folder on Yandex
	 * @return 		array
	 */
	public function publish( $remote ) {

		$xml  = '';
		$xml .= '<propertyupdate xmlns="DAV:">';
		$xml .= '<set>';
		$xml .= '<prop>';
		$xml .= '<public_url xmlns="urn:yandex:disk:meta">true</public_url>';
		$xml .= '</prop>';
		$xml .= '</set>';
		$xml .= '</propertyupdate>';

		$this->request()->send(
			'PROPPATCH',
			$remote,
			[
				[ 'Content-Type',   'application/xml; charset="utf-8"' ],
				[ 'Content-Length', strlen( $xml ) ]
			],
			[
				[ CURLOPT_POSTFIELDS, $xml ]
			]
		);

		if ( $this->request()->getStatus() == 404 ) throw new \Exception( "[Cloudmirrors Yandex Error] File or folder not exists" );

		preg_match( '@<public_url xmlns="urn:yandex:disk:meta">(.*?)</public_url>@si', $this->request()->getContent(), $out );

		return ( $this->request()->getStatus() == 207 AND isset( $out[ 1 ] )  ) ? $out[ 1 ] : [];
	}

	/**
	 *
	 * @param 		$remote 			File or folder on Yandex
	 * @return 		bool
	 */
	public function unpublish( $remote ) {

		$this->request()->reset();

		$xml  = '';
		$xml .= '<propertyupdate xmlns="DAV:">';
		$xml .= '<remove>';
		$xml .= '<prop>';
		$xml .= '<public_url xmlns="urn:yandex:disk:meta" />';
		$xml .= '</prop>';
		$xml .= '</remove>';
		$xml .= '</propertyupdate>';

		$this->request()->send(
			'PROPPATCH',
			$remote,
			[
				[ 'Content-Type',   'application/xml; charset="utf-8"' ],
				[ 'Content-Length', strlen( $xml ) ]
			],
			[
				[ CURLOPT_POSTFIELDS, $xml ]
			]
		);

		if ( $this->request()->getStatus() == 404 ) throw new \Exception( "[Cloudmirrors Yandex Error] File or folder not exists" );

		return ( $this->request()->getStatus() == 207 ) ? TRUE : FALSE;
	}

	/**
	 *
	 * @param 		$remote 			File or folder on Yandex
	 * @return 		mixed
	 */
	public function hasPublish( $remote ) {

		$this->request()->reset();

		$xml  = '';
		$xml .= '<propfind xmlns="DAV:">';
    	$xml .= '<prop>';
    	$xml .= '<public_url xmlns="urn:yandex:disk:meta"/>';
		$xml .= '</prop>';
		$xml .= '</propfind>';

		$this->request()->send(
			'PROPFIND',
			$remote,
			[
				[ 'Depth',          '1' ],
				[ 'Content-Length', strlen( $xml ) ]
			],
			[
				[ CURLOPT_POSTFIELDS, $xml ]
			]
		);

		if ( $this->request()->getStatus() == 404 ) throw new \Exception( "[Cloudmirrors Yandex Error] File or folder not exists" );

		if ( $this->request()->getStatus() == 207 ) {

			preg_match( '@<public_url xmlns="urn:yandex:disk:meta">(.*?)</public_url>@si', $this->request()->getContent(), $out );

			return $out[ 1 ];
		}

		return FALSE;
	}

	/**
	 *
	 * @param 		$fileOrPath 			String to split
	 * @return 		array
	 */
	protected function splitter( $fileOrPath ) {

		$return    = [];
		$params    = explode( '/', $fileOrPath );
		$lastParam = end( $params );

		if ( empty( $lastParam ) ) {

			array_pop( $params );

			$return[ 'type' ]  = 'path';
			$return[ 'path' ]  = $params;
		}
		else {

			$name = explode( '.', $lastParam );
			$ext  = end( $name );

			array_pop( $name );
			array_pop( $params );

			$return[ 'type' ] = 'file';
			$return[ 'path' ] = $params;
			$return[ 'file' ] = [ 'name' => implode( '.', $name ), 'ext' => $ext ];
		}

		return $return;
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