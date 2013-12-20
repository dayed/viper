<?php

/**
 * Nothing should ever be given here. Sod off!
 */
Route::any('/', function() {
	throw new Viper\Exception('No method supplied', 'method');
});
/**
 * A nice little group, holding all the use related methods.
 * 
 * If you can't work out what each route does, despite the fact that in most
 * the name is mentioned no less than three times, you shouldn't be looking
 * at this file.
 */
Route::group(array('prefix' => '/user'), function() {
	
	Route::post('/login', array(
		'as'	=> 'user.login',
		'uses'	=> 'UserController@login'
	));
	
	Route::get('/authorise', array(
		'as'	=> 'user.authorise',
		'uses'	=> 'UserController@authorise'
	));
	
	Route::post('/logout', array(
		'as'	=> 'user.logout',
		'uses'	=> 'UserController@logout'
	));
	
	Route::post('/register', array(
		'as'	=> 'user.register',
		'uses'	=> 'UserController@register'
	));
	
});
/**
 * This is for the admin panel!!
 */
Route::group(array('prefix' => '/admin'), function() {
	
	
});