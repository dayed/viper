<?php

use Viper\Exception as Viper_Exception;
use Viper\Model\User_Token;

class User_BaseController extends BaseController {
	
	protected function _login() {
		if($this->user) {
			$token = new User_Token;
			$token->generate();
			
			if($this->user->token()->save($token)) {
				return $token;
			}
		}
	}
	
	protected function _logout() {
		if($this->user) {
			/**
			 * Grab the active token. We do this just incase there is another
			 * token floating around.
			 */
			$active_token = $this->user->token;
			/**
			 * Now remove that token and return success. Seeing as this is a 
			 * simple method/endpoint, no data is required on the return.
			 */
			if($active_token) {
				$active_token->delete();
				
				return true;
			}
			/**
			 * If the user doesn't have an active token, we'll want to let
			 * the developer know this, as this functionality shouldn't
			 * be accessible without a token.
			 */
			throw new Viper_Exception('No active token', 'token');
		}
		/**
		 * If no token is provided, or the user property is null, we want
		 * to throw an exception of token type, as this should not happen,
		 * if it does, the developer is being naughty and calling this
		 * incorrectly.
		 */
		throw new Viper_Exception('No token provided', 'token');
	}
	
}