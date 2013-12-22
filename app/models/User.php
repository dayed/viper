<?php namespace Viper\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Hash;

class User extends Eloquent {
	
	protected $table = 'users';
	
	protected $fillable = array(
		'username', 'password', 'email', 'active'
	);
	/**
	 * We don't ever want the user id, the password hash, the token or the 
	 * reset code returned. 
	 *
	 * @var array
	 */
	protected $hidden = array(
		'id', 'password', 'token', 'reset'
	);
	/**
	 * Keys to return when model is converted to an array.
	 *
	 * @var array
	 */
	protected $appends = array(
		'is_active'
	);
	/**
	 * Definition of the relationship between the user and the user profile.
	 * 
	 * @return \Viper\Model\User\Profile
	 */
	public function profile() {
		return $this->hasOne('\Viper\Model\User_Profile');
	}
	/**
	 * Definition of the relationship between the user and the user token.
	 * 
	 * @return \Viper\Model\User\Token
	 */
	public function token() {
		return $this->hasOne('\Viper\Model\User_Token');
	}
	/**
	 * Definition of the relationship between the user and the user reset code.
	 * 
	 * @return \Viper\Model\User\Reset
	 */
	public function reset() {
		return $this->hasOne('\Viper\Model\User_Reset');
	}
	/**
	 * Definition of the relationship between the user and the user gamedata.
	 * 
	 * @return \Viper\Model\User\Gamedata
	 */
	public function data() {
		return $this->hasMany('\Viper\Model\User_Gamedata');
	}
	/**
	 * This is setup so that we don't have to hash new passwords, and we can just
	 * let them be auto-hashed.
	 * 
	 * @param string $value
	 */
	public function setPasswordAttribute($value) {
		$this->attributes['password'] = Hash::make($value);
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