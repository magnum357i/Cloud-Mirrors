<?php

include( '../autoload.php' );

$client = new cloudmirrors\Services\Yandex();

$client->setLogin( 'username', 'pass' );

//echo $client->hasFile( 'test/example.txt' );

//$client->uploadFile( 'example.txt', 'test/' );

//$client->hasPublish( 'test/example.txt' );

//$client->delete( 'test/example.txt' );

//$client->createFolder( 'test/sub_test/' );

//echo $client->getDiskInfo();

//echo $client->publish( 'test/example.txt' );

//$client->unpublish( 'test/example.txt' );

//$client->moveFile( 'test/example.txt', 'test/sub_test/' );

//$client->rename( 'test/example.txt', 'example2' );

//$client->downloadFile( '/test/example.txt', __DIR__ . '\\' ) );

//var_dump( $client->listDirectory( '/testa' ) );