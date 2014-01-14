<?php

use Viper\Exception as Viper_Exception;
use Illuminate\Support\Facades\Validator;
use Viper\Model\User;
use Viper\Model\User_Token;

class UserController extends User_BaseController
{
    /**
     * Logs a user in based on their username and password credentials.
     *
     * Will throw a Viper_Exception of argument, validation or token type.
     *
     * @return Illuminate\Http\JsonResponse
     * @throws Viper_Exception
     */
    public function login()
    {
        /**
         * Check that there isn't currently a logged in user. This is mainly
         * to make sure that developers aren't passing a token.
         */
        if (!$this->user) {
            $validator = Validator::make(
                $this->arguments,
                array(
                    'username' => array('required', 'exists:users'),
                    'password' => array('required')
                ));
            /**
             * If validation fails on the provided arguments, throw an exception
             * of type validation.
             */
            if ($validator->fails()) {
                throw new Viper_Exception($validator->messages(), 'validation');
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
            if (in_array('profile', $this->with)) {
                $with[] = 'profile';
            }
            /**
             * This should never fail, seeing as the validation should have picked
             * this up, but that's not to say that it won't ever fail.
             */
            try {
                $user = User::with($with)->where('username', $this->arguments['username'])->firstOrFail();
            } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                /**
                 * Rethrow the exception with something the API can respond with.
                 */
                throw new Viper_Exception('Incorrect username', 'argument', $e);
            }
            /**
             * Check the provided password with that saved for the user. If the
             * passwords do not match, throw an exception of argument type.
             */
            if (!Hash::check($this->arguments['password'], $user->password)) {
                throw new Viper_Exception('Incorrect password', 'argument');
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
            if (!($token instanceof User_Token)) {
                throw new Viper_Exception('Unable to generate user token', 'unknown');
            }
            /**
             * We manually add this on just to make sure.
             */
            $user_response['token'] = $token->token;
            /**
             * If a system has many games, than it's only sensible for a user to
             * have multiple gamedata entries, and seeing as this is a one to many
             * relationship, we want to get only the record specific to the game
             * that the API credentials are for.
             */
            if (in_array('gamedata', $this->with)) {
                $gamedata = $this->game->data()->forUser($this->user->id);
                $user_response['gamedata'] = $gamedata ? $gamedata->toArray() : array();
            }
            /**
             * Here we fire an even for user.login, which allows you, yes you, to
             * subscribe to this event and do some funky stuff, like, award XP or soft
             * currency if this is the first time they've signed in today.
             */
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
     * This method is a bit of cheat. It's used to allow the game to check whether
     * a token is valid or not, it'd typically be used for a mobile game where
     * the game hasn't been ran in a while, but there's still a user token stored.
     *
     * We return the user data, providing the user property has been populated.
     *
     * @return Illuminate\Http\JsonResponse
     */
    public function authorise()
    {
        if ($this->user) {
            /**
             * This bit is the same as with login(), where we compile a list
             * of the relationships that we should provide should the token
             * be successful.
             */
            $with = array();

            if (in_array('profile', $this->with)) {
                $with[] = 'profile';
            }

            $this->user->load($with);

            $user_response = $this->user->toArray();

            if (in_array('gamedata', $this->with)) {
                $gamedata = $this->game->data()->forUser($this->user->id)->get();
                $user_response['gamedata'] = $gamedata ? $gamedata->toArray() : array();
            }
            /**
             * Return the user data that corresponds with the token.
             */
            return $this->success(array('user' => $user_response));
        }
        /**
         * This is the exception that proves the rule, which is ironic considering the
         * rule is that there will be a method that doesn't throw an exception, ahaha, man
         * I'm funny.
         *
         * Anyway, in case you're the curious kind and have survived this long without
         * electricuting yourself by sticking a fork in a plug socket, you deserve an explanation.
         * Well, we don't return anything here or throw an exception, simply because on the off chance
         * that the supplied token is invalid, our lovely magical constructor will have thrown
         * a wobbler and responded with an error.
         *
         * 5 minutes have passed since writing the above, and my confidence in the fact that you'll
         * never hit this point has been dampened thanks to the power of hindsight, so I'm returning
         * an error after all.
         */
        return $this->failure('Invalid token', 'token');
    }

    /**
     * This will log the user out. It's basically an endpoint for the helper function
     *
     * @return Illuminate\Http\JsonResponse
     * @throws Viper_Exception
     */
    public function logout()
    {
        try {
            if ($this->_logout()) {
                Event::fire('user.logout', array('user' => $this->user));
                return $this->success();
            }
        } catch (Viper_Exception $e) {
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
    public function register()
    {
        /**
         * Check that there isn't currently a logged in user. This is mainly
         * to make sure that developers aren't passing a token.
         */
        if (!$this->user) {
            $validator = Validator::make(
                $this->arguments,
                array(
                    'username' => array('required', 'unique:users', 'min:3'),
                    'password' => array('required', 'min:6'),
                    'email' => array('required', 'email', 'unique:users')
                ));

            /**
             * If validation fails on the provided arguments, throw an exception
             * of type validation.
             */
            if ($validator->fails()) {
                throw new Viper_Exception($validator->messages, 'validation');
            }
            /**
             * Create a new user with the provided username, password and email
             */
            $user = User::create(array(
                'username' => $this->arguments['username'],
                'password' => $this->arguments['password'],
                'email' => $this->arguments['email']
            ));

            if ($user) {
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
                if (array_key_exists('first_name', $this->arguments)) {
                    $profile_data['first_name'] = $this->arguments['first_name'];
                }
                if (array_key_exists('last_name', $this->arguments)) {
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
                if (array_key_exists('autologin', $this->arguments)) {
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
                    } catch (Exception $e) {
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
    public function reset()
    {
        /**
         * Make sure that we actually have a user token otherwise we'll have
         * no idea who we're creating a reset for.
         */
        if ($this->user) {
            /**
             * Load the reset on the off chance that there is already one.
             */
            $this->user->load('reset');
            /**
             * Check to see if a user reset exists, and create one if not.
             */
            if ($this->user->reset && $this->user->reset->count() == 0) {
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
            Mail::send('emails.users.reset', $user_data, function ($message) use ($user_data) {
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