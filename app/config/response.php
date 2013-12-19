<?php
/**
 * Contains error codes and corresponding http status code, organised 
 * by ident.
 * 
 * @var array
 */
return array(
	'none'			=> array(
		'code'	=> 0,
		'http'	=> 200
	),
	'unexpected'	=> array(
		'code'	=> 1,
		'http'	=> 500
	),
	'unknown'		=> array(
		'code'	=> 2,
		'http'	=> 405
	),
	'api'			=> array(
		'code'	=> 3,
		'http'	=> 403
	),
	'signature'		=> array(
		'code'	=> 4,
		'http'	=> 401
	),
	'permission'	=> array(
		'code'	=> 5,
		'http'	=> 403
	),
	'incomplete'	=> array(
		'code'	=> 6,
		'http'	=> 400
	),
	'argument'		=> array(
		'code'	=> 7,
		'http'	=> 400
	),
	'token'			=> array(
		'code'	=> 8,
		'http'	=> 401
	),
	'method'		=> array(
		'code'	=> 9,
		'http'	=> 405
	),
	'validation'	=> array(
		'code'	=> 10,
		'http'	=> 400
	),
	'unavailable'	=> array(
		'code'	=> 11,
		'http'	=> 503
	)
);