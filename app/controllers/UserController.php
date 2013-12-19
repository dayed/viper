<?php namespace Viper\Controller;

use Viper\Exception as Viper_Exception;

class UserController extends User_BaseController {
	/**
	 * Logs a user in based on their username and password credentials.
	 * 
	 * Will throw a Viper_Exception of argument, validation or token type.
	 * 
	 * @return Illuminate\Http\JsonResponse
	 * @throws Viper_Exception
	 */
	public function login() {
		/**
		 * Check that there isn't currently a logged in user. This is mainly
		 * to make sure that developers aren't passing a token.
		 */
		if(!$this->user) {
			$validator = Validator::make(
				$this->arguments,
				array(
					'username' => array('required', 'exits:users'),
					'password' => array('required')
				));
			/**
			 * If validation fails on the provided arguments, throw an exception
			 * of type validation.
			 */
			if($validator->fails()) {
				throw new Viper_Exception($validator->messages, 'validation');
			}
			/**
			 * Setup the default models that we need for this, as well as add
			 * any others that were requested.
			 */
			$with = array('token');
			/**
			 * The idea here is that some developers may wish to have the the user
			 * profile, gamedata and other relationships returned upon logging in, 
			 * to avoid making multiple API calls.
			 */
			if(isset($this->with['profile'])) {
				$with[] = 'profile';
			}
			/**
			 * This should never fail, seeing as the validation should have picked
			 * this up, but that's not to say that it won't ever fail.
			 */
			try {
				$user = User::with($with)->where('username', $this->arguments['username'])->firstOrFail();
			} catch(Illuminate\Database\Eloquent\ModelNotFoundException $e) {
				/**
				 * Rethrow the exception with something the API can respond with.
				 */
				throw new Viper_Exception('Incorrect username', 'argument', $e);
			}
			/**
			 * Check the provided password with that saved for the user. If the
			 * passwords do not match, throw an exception of argument type.
			 */
			if(!Hash::check($user->password, $this->arguments->password)) {
				throw new Viper_Exception('Incorrect password', 'argument', $e);
			}
			/**
			 * This means we can be lazy and abstract out the token removal for other
			 * sessions. It's a bit of a cheat, but will give other methods the impression
			 * that a user has already logged in, even though technically, they haven't.
			 */
			$this->user = $user;
			/**
			 * Get the base array for building a response, and the current active
			 * token if there is one.
			 */
			$user_response = $this->user->toArray();
			/**
			 * Destroying the user token is the same process we use for logout, so 
			 * we just call the helper function for this.
			 */
			try {
				$this->_logout();
			} catch(Viper_Exception $e) {
				/**
				 * We don't rethrow the Viper_Exception because the only ones returned
				 * by this method mean that we don't have a current active token, which
				 * is fine.
				 */
			} catch(Exception $e) {
				/**
				 * We do however, want to catch anything that isn't a Viper_Exception
				 * and throw a new Viper_Exception of unknown type.
				 */
				throw new Viper_Exception('Unknown Error', 'unknown', $e);
			}
			/**
			 * Create a new token, generate the code and assign to the user.
			 */
			try {
				$token = $this->_login();
			} catch(Viper_Exception $e) {
				throw new Viper_Exception('Unable to generate user token', 'unknown');
			}
			/**
			 * We manually add this on just to make sure.
			 */
			$user_response['token'] = $token->toArray();
			/**
			 * If a system has many games, than it's only sensible for a user to
			 * have multiple gamedata entries, and seeing as this is a one to many
			 * relationship, we want to get only the record specific to the game
			 * that the API credentials are for.
			 */
			if(isset($this->with['gamedata'])) {
				$gamedata = $this->game->data()->forUser($user->id);
				$user_response['gamedata'] = $gamedata ? $gamedata->toArray() : array();
			}
			Event::fire('user.login', array('user' => $this->user));
			/**
			 * We've got everything we need, now return the user response under the
			 * user entry.
			 */
			return $this->success(array('user' => $user_response));
		}
		/**
		 * Just to catch any lazy developers who are not paying attention to what
		 * they're passing to the API.
		 */
		throw new Viper_Exception('Already logged in', 'token');
	}
	/**
	 * This will log the user out. It's basically an endpoint for the helper function
	 * 
	 * @return Illuminate\Http\JsonResponse
	 * @throws Viper_Exception
	 */
	public function logout() {
		try {
			if($this->_logout()) {
				Event::fire('user.logout', array('user' => $this->user));
				return $this->success();
			}
		} catch(Viper_Exception $e) {
			throw $e;
		}
	}
	/**
	 * Registers a new user and creates an associated profile, whether profile data
	 * is provided or not.
	 * 
	 * @return Illuminate\Http\JsonResponse
	 * @throws Viper_Exception
	 */
	public function register() {
		/**
		 * Check that there isn't currently a logged in user. This is mainly
		 * to make sure that developers aren't passing a token.
		 */
		if(!$this->user) {
			$validator = Validator::make(
				$this->arguments,
				array(
					'username'	=> array('required', 'unique:users', 'min:3'),
					'password'	=> array('required', 'min:6'),
					'email'		=> array('required', 'email', 'unique:users')
				));
			
			/**
			 * If validation fails on the provided arguments, throw an exception
			 * of type validation.
			 */
			if($validator->fails()) {
				throw new Viper_Exception($validator->messages, 'validation');
			}
			/**
			 * Create a new user with the provided username, password and email
			 */
			$user = User::create(array(
				'username'	=> $this->arguments['username'],
				'password'	=> $this->arguments['password'],
				'email'		=> $this->arguments['email']
			));
			
			if($user) {
				/**
				 * If the user was created, populate the user property so we can do fun things
				 * like using $this->_login()
				 */
				$this->user = $user;
				
				$profile_data = array();
				/**
				 * Check if the user provided a first name and or last name, if so
				 * add to the profile data so we don't have a blank profile.
				 * 
				 * Not that it matters, it can be updated at a later date.
				 */
				if(array_key_exists('first_name', $this->arguments)) {
					$profile_data['first_name'] = $this->arguments['first_name'];
				}
				if(array_key_exists('last_name', $this->arguments)) {
					$profile_data['last_name'] = $this->arguments['last_name'];
				}
				/**
				 * Create the user profile with either the empty array or the above
				 * data.
				 */
				$profile = new User_Profile($profile_data);
				/**
				 * Associate the profile with the user.
				 */
				$this->user->profile()->save($profile);
				/**
				 * Grab the default response for returning.
				 */
				$user_response = $this->user->toArray();
				/**
				 * If the autologin argument is provided, a token will be generated
				 * so that the user can jump straight in and start using their account.
				 */
				if(array_key_exists('autologin', $this->arguments)) {
					/**
					 * Like with the login endpoint, use the helper function
					 * and catch any exceptions.
					 * 
					 * Here we just listen for Exception because any error will
					 * result in the same response hitting the user.
					 */
					try {
						$token = $this->_login($user);
						$user_response['token'] = $token->toArray();
					} catch(Exception $e) {
						throw new Viper_Exception('Unable to generate user token', 'unknown', $e);
					}
				}
				Event::fire('user.register', array('user' => $this->user));
				/**
				 * We've got everything we need, now return the user response under the
				 * user entry.
				 */
				return $this->success(array('user' => $user_response));
			}
			/**
			 * This is one of those "Should never happen" situations, but still,
			 * it's better to be on the safeside.
			 */
			return Viper_Exception('Unable to create user', 'unknown');
		}
		/**
		 * Just to catch any lazy developers who are not paying attention to what
		 * they're passing to the API.
		 */
		throw new Viper_Exception('Already logged in', 'token');
	}
	/**
	 * Allows users to reset their password. They are sent an email with a code
	 * that they can use to reset their password.
	 * 
	 * @return Illuminate\Http\JsonResponse
	 * @throws Viper_Exception
	 */
	public function reset() {
		/**
		 * Make sure that we actually have a user token otherwise we'll have
		 * no idea who we're creating a reset for.
		 */
		if($this->user) {
			/**
			 * Load the reset on the off chance that there is already one.
			 */
			$this->user->load('reset');
			/**
			 * Check to see if a user reset exists, and create one if not.
			 */
			if($this->user->reset && $this->user->reset->count() == 0) {
				/**
				 * Create the user reset and generate a unique code.
				 */
				$reset = new User_Reset;
				$reset->generate();
				/**
				 * Assign the reset to the user.
				 */
				$this->user->reset()->save($reset);
			}
			Event::fire('user.reset', array('user' => $this->user));
			/**
			 * Get the user data for the email. Not all of this is used in the default
			 * email, but it allows for custom emails to contain more.
			 */
			$user_data = $this->user->toArray();
			$user_data['name'] = $this->user->profile->name;
			$user_data['reset'] = $this->user->reset->toArray();
			/**
			 * Send the email.
			 */
			Mail::send('emails.users.reset', $user_data, function($message) use($user_data) {
				$message->to($user_data['email'], $user_data['name'])->subject('Password Reset');
			});
			/**
			 * Unfortunately because of the way Laravels mail library works,
			 * we can't tell whether or not an email was actually sent. It's safe 
			 * to let the end developer to control this side.
			 */
			return $this->success();
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