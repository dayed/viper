<?php

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Viper\Exception as Viper_Exception;
use Viper\Model\Game;
use Viper\Model\User;

class BaseController extends Controller {
	/**
	 * Contains an instance of the current user based on the authentication
	 * token passed with the current request.
	 * 
	 * @var Viper\Model\User
	 */
	protected $user;
	/**
	 * Contains an instance of the current game based on the key passed with
	 * the current request.
	 *
	 * @var Viper\Model\Game
	 */
	protected $game;
	/**
	 * Contains all the current arguments for the request.
	 *
	 * @var array
	 */
	protected $arguments = array();
	/**
	 * When making GET requests, certain endpoints will allow for the return
	 * of associated models and data, this contains the list of requested
	 * relationships.
	 *
	 * @var array
	 */
	protected $with = array();
	/**
	 * At the start of every call we grab the information and populate the
	 * above properties, this makes the system smooth and saves having to
	 * check signatures and grab information manually.
	 */
	public function __construct() {
		if(Input::has('key')) {
			/**
			 * The request has the key attribute, so we know that it's an API request,
			 * now we can populate the game property so the rest of the system
			 * knows what information to access.
			 */
			$key = Input::get('key');
			$game = Game::where('key', $key)->first();
			
			if($game && $game->count() > 0) {
				if($game->is_active === false) {
					/**
					 * The game isn't active, so we'll throw an exception.
					 */
					throw new Viper_Exception('Inactive Game', 'api');
				}
				
				$this->game = $game;				
				$secret = $this->game->secret;
				$method = strtolower(Request::getMethod());

				if($method === 'post') {
					/**
					 * The request was a POST request, so there will definitely be
					 * arguments and a signature.
					 */
					if(Input::has('arguments') && Input::has('signature')) {
						$arguments = Input::get('arguments');
						$signature = Input::get('signature');

						if(hash_hmac('sha1', $arguments, $secret, false) !== $signature) {
							/**
							 * The HMAC doesn't match, throw an exception.
							 */
							throw new Viper_Exception('Invalid Signature', 'signature');
						}

						$this->arguments = json_decode($arguments, true);
					} else {
						/**
						 * If a POST request was made, without arguments or a signature, 
						 * then throw an exception.
						 */
						throw new Viper_Exception('Incomplete Request', 'incomplete');
					}
				} elseif($method === 'get') {
					/**
					 * If the with attribute was provided, then populate the list.
					 */
					if(Input::has('with')) {
						$with = Input::get('with');
						
						$this->with = explode(',', $with);
					}
				}
				/**
				 * If the token attribute is provided, then we know that this request
				 * is authenticated for a specific user, so grab that user and
				 * populate the user property so the rest of the system knows.
				 */
				if(Input::has('token')) {
				
					$token = Input::get('token');
					$user_token = User_Token::with('user')->where('token', $token)->first();
					
					if($user_token && $user_token->count() > 0) {
						/**
						 * Set the user.
						 */
						$this->user = $user_token->user;
					} else {
						/**
						 * The token is invalid, so we should throw an exception.
						 */
						throw new Viper_Exception('Invalid Token', 'token');
					}
				}
			} else {
				/**
				 * The API credentials do not match our records, so we throw
				 * an excpetion.
				 */
				throw new Viper_Exception('Unknown Game', 'api');
			}
		} else {
			/**
			 * All requests should contain a key attribute.
			 */
			throw new Viper_Exception('Invalid Credentials',  'api');
		}
	}
	/**
	 * Helper function to provide a response for the API endpoint. This should
	 * never be called within the code.
	 * 
	 * @param string $status Should be success or failure
	 * @param array $data The data to pass back, can be an empty array
	 * @param int $code The HTTP status code to return, 200 for success
	 * @param array $error An array of error information, including code and message
	 * @return Illuminate\Http\JsonResponse
	 */
	public static function response($status = 'success', $data = array(), $code = 200, $error = array()) {
		$response = array();
		/**
		 * The data attribute only exists for successful calls, and the 
		 * error attribute only exists for unsuccessful calls.
		 */
		if($status == 'success' || $status == 'failure') {
			$response['status'] = $status;
			if($status == 'success') {
				$response['data'] = $data;
			} elseif($status == 'failure') {
				$response['error'] = $error;
			}
		}
		return Response::json($response, $code);
	}
	/**
	 * Helper function to return errors in a nice way, this should never be called
	 * from within the code, instead an exception should be thrown and this will be called
	 * from the App::error() block.
	 * 
	 * @param int $code The internal error code
	 * @param int $http The HTTP status code
	 * @param string $message A string explaining the error
	 * @return Illuminate\Http\JsonResponse
	 */
	public static function error($code, $http, $message) {
		return self::response('failure', array(), $http, array(
			'code'		=> $code,
			'message'	=> $message
		));
	}
	/**
	 * Helper function to return a successful response, this should be used internally
	 * to return from a method.
	 * 
	 * @param array $data
	 * @return Illuminate\Http\JsonResponse
	 */
	public function success($data = array()) {
		return self::response('success', $data);
	}
	
}