<?php namespace Viper\Controllers;
/**
 * Base Controller
 * 
 * @author Ollie Read <labs@ollieread.com>
 * @version 1.0-alpha
 * @license http://opensource.org/licenses/MIT MIT
 */

use Viper\Core\Facades\ViperAuth as Auth;
use Viper\Core\Facades\ViperResponse as Response;

use Illuminate\Routing\Controller;

/**
 * Provide some base functionality and access for other controllers.
 */
class BaseController extends Controller
{
	
    public function __construct()
    {
		$this->beforeFilter(function() {
			if(!Auth::game()) {
				Response::error('Invalid Game', 'api');
			}
		});
    }
	/**
	 * Helper to check whether the API credentials were valid or not.
	 * 
	 * @todo See if this is ever used, anywhere, as I doubt it cane be.
	 * @return bool
	 */
	protected function isValidGame()
	{
		return !is_null(Auth::game());
	}
	/**
	 * Helper to check whether the token provided was valid.
	 * 
	 * @return bool
	 */
	protected function isValidUser()
	{
		return !is_null(Auth::user());
	}
	/**
	 * Helper to return a successful response.
	 * 
	 * @param string|null $name
	 * @param mixed $data
	 * @param int $code
	 * @return string
	 */
	protected function success($name = null, $data = null, $code = 200)
	{
		if(!empty($name) && !empy($data)) {
			return Response::add($name, $data)->toJson();
		}
		
		return Response::toJson();
	}
	/**
	 * Helper to return a failure. It does this by asking the
	 * \Viper\Core\Response class to throw an exception.
	 * 
	 * @param mixed $message
	 * @param string $type
	 */
	protected function failure($message, $type)
	{
		Response::error($message, $type);
	}
	/**
	 * Fire an event for the current route.
	 * 
	 * @param array $payload
	 */
	protected function fireEvent($payload = [])
	{
		Event::fire(Request::route(), $payload);
	}

}