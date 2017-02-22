<?php
/*
 * First authored by Brian Cray
 * License: http://creativecommons.org/licenses/by/3.0/
 * Contact the author at http://briancray.com/
 *
 * @modified Ika 2017-02-22 Updated for PHP7.0.
 */
 
ini_set('display_errors', 0);

if( !array_key_exists('longurl',$_REQUEST) ) {
    die( 'Please pass a longurl.' );
}

$url_to_shorten = get_magic_quotes_gpc() ? stripslashes(trim($_REQUEST['longurl'])) : trim($_REQUEST['longurl']);

if(!empty($url_to_shorten) && preg_match('|^https?://|', $url_to_shorten))
{
	require('config.php');

	// check if the client IP is allowed to shorten
	if($_SERVER['REMOTE_ADDR'] != LIMIT_TO_IP)
	{
		die('You are not allowed to shorten URLs with this service.');
	}
	
	// check if the URL is valid
	if(CHECK_URL)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_to_shorten);
		curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		$response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($response_status == '404')
		{
			die('Not a valid URL');
		}
		
	}
	
	// check if the URL has already been shortened
    $resulti = $mysqli->query('SELECT id FROM ' . DB_TABLE. ' WHERE long_url="' . $mysqli->real_escape_string($url_to_shorten) . '";');
    $row = $resulti->fetch_assoc();
    $already_shortened = null;
	if( $row && !empty($already_shortened = $row['id']))
	{
		// URL has already been shortened
		$shortened_url = getShortenedURLFromID($already_shortened);
	}
	else
	{
		// URL not in database, insert
		$mysqli->query('LOCK TABLES ' . DB_TABLE . ' WRITE;');
		$mysqli->query('INSERT INTO ' . DB_TABLE . ' (long_url, created, creator) VALUES ("' . $mysqli->real_escape_string($url_to_shorten) . '", "' . time() . '", "' . $mysqli->real_escape_string($_SERVER['REMOTE_ADDR']) . '");');
		$shortened_url = getShortenedURLFromID($mysqli->insert_id);
		$mysqli->query('UNLOCK TABLES;');
	}
	echo BASE_HREF . $shortened_url;
} else {
    header("HTTP/1.0 400 Bad Request");
    die( 'Bad Request' );
}

function getShortenedURLFromID ($integer, $base = ALLOWED_CHARS)
{
	$length = strlen($base);
    $out = '';
	while($integer > $length - 1)
	{
		$out = $base[$integer%$length] . $out;
		$integer = floor( $integer / $length );
	}
	return $base[$integer] . $out;
}
