<?php namespace Viper\Model;

class User extends Eloquent {
	
	protected $table = 'users';
	
	protected $fillable = array(
		'username', 'password', 'email'
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
	 * Definition of the relationship between the user and the user profile.
	 * 
	 * @return \Viper\Model\User\Profile
	 */
	public function profile() {
		return $this->hasOne('User_Profile');
	}
	/**
	 * Definition of the relationship between the user and the user token.
	 * 
	 * @return \Viper\Model\User\Token
	 */
	public function token() {
		return $this->hasOne('User_Token');
	}
	/**
	 * Definition of the relationship between the user and the user reset code.
	 * 
	 * @return \Viper\Model\User\Reset
	 */
	public function reset() {
		return $this->hasOne('User_Reset');
	}
	/**
	 * Definition of the relationship between the user and the user gamedata.
	 * 
	 * @return \Viper\Model\User\Gamedata
	 */
	public function data() {
		return $this->hasMany('User_Gamedata');
	}
	
}