<?php namespace Viper\Controllers;
/**
 * User Controller
 * 
 * @author Ollie Read <labs@ollieread.com>
 * @version 1.0-alpha
 * @license http://opensource.org/licenses/MIT MIT
 */

use Viper\Core\Facades\ViperAuth as Auth;
use Viper\Core\Facades\ViperResponse as Response;
use Viper\Core\Facades\ViperRequest as Request;
use Viper\Models as Models;

use Illuminate\Support\Facades\Hash;

class UserController extends User_BaseController
{
    /**
     * Authenticates based on their username and password credentials.
     *
     * @return string
     */
    public function authenticate()
    {
        /**
         * Check that there isn't currently a logged in user. This is mainly
         * to make sure that developers aren't passing a token.
         */
        if (!$this->isValidUser()) {
			
			$parameters = [
				$this->argument('username'),
				$this->argument('password')
			];
			
			$validation = Models\User::validate($parameters);
			
            if($validation == true) {
				/**
				 * If validation fails on the provided arguments, throw an exception
				 * of type validation.
				 */
				try {
					$user = Models\User::where('username', $parameters['username'])->firstOrFail();
				} catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
					/**
					 * Rethrow the exception with something the API can respond with.
					 */
					Response::error('Incorrect username', 'argument');
				}
				/**
				 * Check the provided password with that saved for the user. If the
				 * passwords do not match, throw an exception of argument type.
				 */
				if (!Hash::check($parameters['password'], $user->password)) {
					Response::error('Incorrect password', 'argument');
				}
				/**
				 * This means we can be lazy and abstract out the token removal for other
				 * sessions. It's a bit of a cheat, but will give other methods the impression
				 * that a user has already logged in, even though technically, they haven't.
				 */
				$this->user = $user;
				/**
				 * This is the nice helper function to destroy an existing user token. At this
				 * point we don't care whether this returns false, as a false would indicate that
				 * the user doesn't have an existing token, which is fine.
				 *
				 * The only other time it returns false is if $this->user is empty, which is fine
				 * as that should never happen, it's really just there to catch the rare chance
				 * that it may happen.
				 */
				$this->_logout();
				/**
				 * Create a new token, generate the code and assign to the user.
				 */
				$token = $this->_login();
				/**
				 * We check instanceof rather than is_null() because there shouldn't ever be
				 * a time when this method returns anything but null or User_Token, if it does
				 * you changed core code and you should have really changed it here too. That being
				 * said, if you're seeing this, you forgot to do a usage search :P
				 */
				if (!($token instanceof Models\User_Token)) {
					Response::error('Unable to generate user token', 'unknown');
				}
				/**
				 * Here we fire an event for the current route.
				 */
				$this->fireEvent(['user' => $this->user]);
				/**
				 * We've got everything we need, now return the user response under the
				 * user entry.
				 */
				return Response::add('token', $token)->toJson();
			} else {
				Response::error($validation, 'validate');
			}
        }
        /**
         * Just to catch any lazy developers who are not paying attention to what
         * they're passing to the API.
         */
        Response::error('Already logged in', 'token');
    }

    /**
     * This method is a bit of cheat. It's used to allow the game to check whether
     * a token is valid or not, it'd typically be used for a mobile game where
     * the game hasn't been ran in a while, but there's still a user token stored.
     *
     * We return the user data, providing the user property has been populated.
     *
     * @return string
     */
    public function validate()
    {
        if ($this->isValidUser()) {
            return Response::add('user', Auth::user())->toJson();
        } else {
			Response::error('Invalid token', 'token');
		}
    }

    /**
     * Registers a new user and creates an associated profile, whether profile data
     * is provided or not.
     *
     * @return string
     */
    public function register()
    {
        /**
         * Check that there isn't currently an authenticated user. This is mainly
         * to make sure that developers aren't passing a token.
         */
        if (!$this->isValidUser()) {
			$arguments = [
				Request::argument('username'),
				Request::argument('password'),
				Request::argument('email')
			];
			
			$validation = Models\User::validate($arguments);

            /**
             * If validation fails on the provided arguments, return an error.
             */
            if ($validation !== true) {
                Response::error($validation, 'validation');
            }
            /**
             * Create a new user with the provided username, password and email
             */
            $user = User::create([
                'username'	=> $arguments['username'],
                'password'	=> $arguments['password'],
                'email'		=> $arguments['email']
            ]);

            if ($user) {
                /**
                 * If the user was created, populate the user property so we can do fun things
                 * like using $this->_login()
                 */
                Auth::setUser($user);
				
                $profile_data = [
					'first_name'	=> $this->argument('first_name'),
					'last_name'		=> $this->argument('last_name')
				];
                /**
                 * Create the user profile with either the empty array or the above
                 * data.
                 */
                $profile = new User_Profile($profile_data);
                /**
                 * Associate the profile with the user.
                 */
                Auth::user()->profile()->save($profile);
                /**
                 * If the autologin argument is provided, a token will be generated
                 * so that the user can jump straight in and start using their account.
                 */
                if (Request::query('autologin')) {
                    /**
                     * Like with the login endpoint, use the helper function
                     * and catch any exceptions.
                     *
                     * Here we just listen for Exception because any error will
                     * result in the same response hitting the user.
                     */
                    try {
                        $token = $this->_login($user);
						
						Response::add('token', $token)->toJson();
                    } catch (Exception $e) {
                        Response::error('Unable to generate user token', 'unknown');
                    }
                }
                $this->fireEvent(['user' => $this->user]);
                /**
                 * We've got everything we need, now return the user response under the
                 * user entry.
                 */
                return Repsonse::add('user', $user)->toJson();
            }
            /**
             * This is one of those "Should never happen" situations, but still,
             * it's better to be on the safeside.
             */
            Response::error('Unable to create user', 'unknown');
        }
        /**
         * Just to catch any lazy developers who are not paying attention to what
         * they're passing to the API.
         */
        Response::error('Already logged in', 'token');
    }

    /**
     * Allows users to reset their password. They are sent an email with a code
     * that they can use to reset their password.
     *
     * @return string
     */
    public function reset()
    {
        /**
         * Make sure that we actually have a user token otherwise we'll have
         * no idea who we're creating a reset for.
         */
        if ($this->isValidUser()) {
            /**
             * Load the reset on the off chance that there is already one.
             */
			$user = Auth::user();
			Response::add('user', $user);
            $user->load('reset');
            /**
             * Check to see if a user reset exists, and create one if not.
             */
            if ($user->reset && $user->reset->count() == 0) {
                /**
                 * Create the user reset and generate a unique code.
                 */
                $reset = new Models\User_Reset;
                $reset->generate();
                /**
                 * Assign the reset to the user.
                 */
                $user->reset()->save($reset);
            }
            $this->fireEvent(['user' => $this->user]);
            /**
             * Get the user data for the email. Not all of this is used in the default
             * email, but it allows for custom emails to contain more.
             */
			
			Response::add('reset', $reset);
			
			$user->load('profile');
			$user_data = $user->toArray();
            /**
             * Send the email.
             */
            Mail::send('emails.users.reset', $user_data, function ($message) use ($user_data) {
                $message->to($user_data['email'], $user_data['profile']['name'])->subject('Password Reset');
            });
            /**
             * Unfortunately because of the way Laravels mail library works,
             * we can't tell whether or not an email was actually sent. It's safe
             * to let the end developer to control this side.
             */
            return Response::toJson();
        }
        /**
         * If no token is provided, or the user property is null, we want
         * to throw an exception of token type, as this should not happen,
         * if it does, the developer is being naughty and calling this
         * incorrectly.
         */
        Response::error('No token provided', 'token');
    }

}