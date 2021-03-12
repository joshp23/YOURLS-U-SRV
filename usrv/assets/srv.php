<?php
/*
Plugin Name: U-SRV | helper file
Plugin URI: https://github.com/joshp23/YOURLS-U-SRV
Description: A universal file server for YOURLS | This is the server
Version: 2.3.4
Author: Josh Panter
Author URI: https://unfettered.net
*/
if( !defined( 'YOURLS_ABSPATH' ) ) die();
// Verify that all parameters have been set
// get access key or die
if( isset($_GET['key'])) {
	$key = $_GET['key'];
} else {
	die('FAIL: missing passkey');
}
// get plugin id or die
if( isset($_GET['id'])) {
	$id = $_GET['id'];
} else {
	die('FAIL: missing id');
}
// get file name or die
if( isset($_GET['fn'])) {
	$fn = $_GET['fn'];
	// strip path traversal characters - security
	$fn = preg_replace('/^((\.*)(\/*))*/', '', $fn);
} else {
	die('FAIL: missing filename');
}

// Do security check

// create access lock
$now = round(time()/60);
$lock = md5($now . $id);
// set a cookie to help with javascript calls
$cname = "usrv_" . $id;
setcookie($cname,$now, 0, "/");
// check access key
if($lock !== $key) die('FAIL: bad access key');

/*
 *
 * 	ID 
 *
 *	Check sender and file store location
 *
 *	To add your plugin or script, add a new case below
 *	with an arbitrary ID and file store location and send
 *	the same ID in the GET request.
 *
 * 	Ex. In this example the cache location is stored in the DB
 *	as a YOURLS option. Note the default option name structure:
 *	
 *		case 'ID_VALUE':
 *			$path = yourls_get_option('ID_VALUE_usrv_loc');
 *			break;
 *
 *	Ex. In this example the filepath is just stored here:
 *	
 * 		case 'ID_VALUE':
 *			$path = '/path/to/your/files/');
 *			break;
*/

$path = yourls_get_option('usrv_cache_loc');
if($path == null) $path = dirname(YOURLS_ABSPATH)."/YOURLS_CACHE";
$dir = yourls_get_option( $id.'_usrv_dir' );

switch ($id) {

	case 'usrv_files':
		$path = $path.'/fu';
		break;
	case 'snapshot':
		if($dir == null) $dir = 'preview';
		$path = $path.'/'.$dir;
		break;
	case 'snapshot-alt':
		$path = YOURLS_PLUGINDIR.'/snapshot/assets';
		break;
	case 'iqrcodes':
		if($dir == null) $dir = 'qr';
		$path = $path .'/'. $dir;
		break;
	default:
		$path = $path.'/'.$dir;
}
// Work with the file
$file = $path . '/' . $fn;

// Compare result path to conf path - 2nd security check for path traversal
$loc = pathinfo($file, PATHINFO_DIRNAME);
if( $loc != $path ) die('FAIL: malformed request');

// verify file
if (is_file($file)) {							// if the file exists at this location
	$type = pathinfo($fn, PATHINFO_EXTENSION);	// then get the file extention type
} else {
	die('FAIL: file not found');				// or die
}
/*
 *  Mime Types
 *
 *  Header information must beset explicitly.
 *
 *	Add new file types by using the following format:
 *
 *		case "": 	$ctype=""; break;
 *
*/
switch( $type ) {
	case "jpg": 
		$ctype="image/jpeg";
		break;
	case "png": 
		$ctype="image/png";
		break;
	case "svg": 
		$ctype="image/svg+xml";
		break;
	case "zip": 
		$ctype="application/zip"; 									
		break;
	default: 
		$ctype="pronk";
		break;
}

if($ctype == "pronk") die('file type not supported, please check your configuration');

header('Content-type: ' . $ctype);					// send the correct headers
header('Expires: 0');								// .
header('Content-Length: ' . filesize($file));		// .
readfile($file);									// with the file data
exit;
?>
