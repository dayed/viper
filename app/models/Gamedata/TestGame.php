<?php namespace Viper\Model;

class Gamedata_TestGame extends Gamedata {
	
	protected $table = 'gamedata_testgame';
	
	protected $fillable = array(
		'user_id', 'game_id', 'score'
	);
	
	protected $hidden = array(
		'id', 'user_id', 'game_id'
	);
	
	public function game() {
		return $this->belongsTo('\Viper\Model\Game');
	}
	
}