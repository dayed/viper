<?php namespace Viper\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Gamedata extends Eloquent {
	
	protected $hidden = array(
		'id', 'user_id'
	);
	
	public function scopeForUser($query, $user_id) {
		return $query->where('user_id', $user_id);
	}
	
}