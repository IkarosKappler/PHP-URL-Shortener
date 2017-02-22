<?php
/*
 * First authored by Brian Cray
 * License: http://creativecommons.org/licenses/by/3.0/
 * Contact the author at http://briancray.com/
 *
 * @modified Ika 2017-02-22 Updated for PHP7.0.
 */

ini_set('display_errors', 0);

if( !array_key_exists('url',$_GET) )
{
    die( 'Please pass a short url.' );
}

if(!preg_match('|^[0-9a-zA-Z]{1,6}$|', $_GET['url']))
{
	die('That is not a valid short url');
}

require('config.php');

$shortened_id = getIDFromShortenedURL($_GET['url']);

if(CACHE)
{
	$long_url = null;
    if( file_exists(CACHE_DIR.$shortened_id) && is_readable(CACHE_DIR.$shortened_id) )
    {
        $long_url = file_get_contents(CACHE_DIR . $shortened_id);
    }
    
	if( empty($long_url) || !preg_match('|^https?://|', $long_url) )
	{
		$long_url = fetchURL( $mysqli, $shortened_id );
		@mkdir(CACHE_DIR, 0777);
		$handle = fopen(CACHE_DIR . $shortened_id, 'w+');
		fwrite($handle, $long_url);
		fclose($handle);
	}
}
else
{
    $long_url = fetchURL( $mysqli, $shortened_id );
}

if(TRACK)
{
	$mysqli->query('UPDATE ' . DB_TABLE . ' SET referrals=referrals+1 WHERE id="' . $mysqli->real_escape_string($shortened_id) . '";');
}

header('HTTP/1.1 301 Moved Permanently');
header('Location: ' .  $long_url);
exit;

function getIDFromShortenedURL ($string, $base = ALLOWED_CHARS)
{
	$length = strlen($base);
	$size = strlen($string) - 1;
	$string = str_split($string);
	$out = strpos($base, array_pop($string));
	foreach($string as $i => $char)
	{
		$out += strpos($base, $char) * pow($length, $size - $i);
	}
	return $out;
}

function fetchURL( $mysqli, $shortened_id )
{
    $resulti = $mysqli->query('SELECT long_url FROM ' . DB_TABLE . ' WHERE id="' . $mysqli->real_escape_string($shortened_id) . '";');
    $row = $resulti->fetch_assoc();
    if( !$row )
    {
        header("HTTP/1.0 404 Not Found");
        die( 'Not found' );
    }
    return $row['long_url'];
}