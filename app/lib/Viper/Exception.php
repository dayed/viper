<?php namespace Viper;

use Illuminate\Support\Facades\Config;

class Exception extends \Exception
{
    /**
     * This is the http status code to be returned when a response is sent via the API.
     * It's here because normal Exceptions don't have this attribute.
     *
     * @var int
     */
    protected $status_code;

    public function __construct($message, $type, Exception $previous = null)
    {
        /**
         * We'll want to load the relevant data from the config for this response
         * type. Having it setup like this allows developer to customise to suit
         * their needs.
         */
        $response = Config::get('response.' . $type);

        if ($response) {
            $this->status_code = $response['http'];

            parent::__construct($message, $response['code'], $previous);
        }
    }

    /**
     * Getter function so we can actually access the status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

}