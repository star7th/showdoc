<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';

/**
 * Helper function for getting the expected array
 * from a file as serialized string.
 * Returns an unserialized value from the given file.
 *
 * @param String $filename
 */
function getExpectedValue($path, $filename, $unserialize = true) {
	$path = explode(DIRECTORY_SEPARATOR, $path);
	$content = file_get_contents(dirname(__FILE__) . "/expected/" . array_pop($path) . "/" . $filename);
	return ($unserialize ? unserialize($content) : $content);
}

function setExpectedValue($path, $filename, $data, $serialize = true) {
	$path = explode(DIRECTORY_SEPARATOR, $path);
	return file_put_contents(dirname(__FILE__) . "/expected/" . array_pop($path) . "/" . $filename, $serialize ? serialize($data) : $data );
}
