<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function() {
	return Viper\Controller\BaseController::error(
			Config::get('response.method.code'), 
			Config::get('response.method.http'), 
			'No method supplied'
	);
});

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