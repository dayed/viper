<?php namespace Viper\Model;

class Game extends Eloquent {
	
	protected $table = 'games';
	
	protected $fillable = array(
		'name', 'description', 'key', 'secret'
	);	

}