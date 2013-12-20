<?php

use Viper\Model\Game;
use Viper\Model\User;
use Viper\Model\User_Profile;

class DatabaseSeeder extends Seeder {
	
	public function run() {
		DB::table('games')->delete();
		DB::table('users')->delete();
		
		Game::create(array(
			'name'			=> 'Test Game',
			'model'			=> 'TestGame',
			'description'	=> 'This is a test game for the purpose of testing the API.',
			'active'		=> 1,
			'key'			=> '917e3d32272adedc65985dcb45d37168',
			'secret'		=> '6a1b5d00488f07bf333df2f7700e8222d05e98cc2ba84cc31f9c1977cfdfd77f'
		));
		
		Game::create(array(
			'name'			=> 'Inactive Game',
			'model'			=> 'InactiveGame',
			'description'	=> 'This is an inactive game to help with tests',
			'active'		=> 0,
			'key'			=> '6f8d35b5f4f2fe2d189ae56721fb58e8',
			'secret'		=> 'c0c8bc15912cd0abeaeef744f5881297c2854fcf6d806208bc5d331081f17208'
		));
		
		$user1 = User::create(array(
			'username'		=> 'testuser1',
			'password'		=> 'password',
			'email'			=> 'test@imarealuser.com',
			'active'		=> 1
		));
		
		User_Profile::create(array(
			'user_id'		=> $user1->id,
			'first_name'	=> 'Test',
			'last_name'		=> 'User1',
			'gender'		=> 'm',
			'dob'			=> date('Y-m-d')
		));
		
		$user2 = User::create(array(
			'username'		=> 'testuser2',
			'password'		=> 'password',
			'email'			=> 'test@imnotarealuser.com',
			'active'		=> 0
		));
		
		User_Profile::create(array(
			'user_id'		=> $user2->id,
			'first_name'	=> 'Test',
			'last_name'		=> 'User2',
			'gender'		=> 'm',
			'dob'			=> date('Y-m-d')
		));
	}
	
}