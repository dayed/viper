<?php namespace Viper\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Game extends Eloquent {
	
	protected $table = 'games';
	
	protected $fillable = array(
		'name', 'model', 'description', 'key', 'secret', 'active'
	);
	/**
	 * Keys to return when model is converted to an array.
	 *
	 * @var array
	 */
	protected $appends = array(
		'is_active'
	);
	
	public function data() {
		$model_name = 'Gamedata_' . Str::camel($this->attributes['name']);
		
		return $this->hasMany($model_name);
	}
	
	public function generate() {
		do {
			$key = hash('md5', (Str::random(16) . $this->attributes['id'] . time()), false);
		} while(DB::table($this->table)->where('key', $key)->count() != 0);
		
		$secret = hash('sha256', ($key . Str::random(32)), false);
		
		$this->attributes['key'] = $key;
		$this->attributes['secret'] = $secret;
	}
	/**
	 * Nice little helper function so developers have a boolean to validate
	 * against. Also makes it nicer to use in the system.
	 * 
	 * @return boolean
	 */
	public function getIsActiveAttribute() {
		return $this->attributes['active'] == 1 ? true : false;
	}

}