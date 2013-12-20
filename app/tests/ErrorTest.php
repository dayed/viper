<?php

/**
 * Test all the errors that the system could throw, before we get routed to
 * our desired method.
 */
class ErrorTest extends TestCase {
	
	protected $credentials = array(
		'valid'		=> array(
			'key'			=> '917e3d32272adedc65985dcb45d37168',
			'secret'		=> '6a1b5d00488f07bf333df2f7700e8222d05e98cc2ba84cc31f9c1977cfdfd77f'
		),
		'invalid'	=> array(
			'key'			=> '917e3d32272adedc65985dcb45d3716'
		),
		'inactive'	=> array(			
			'key'			=> '6f8d35b5f4f2fe2d189ae56721fb58e8',
			'secret'		=> 'c0c8bc15912cd0abeaeef744f5881297c2854fcf6d806208bc5d331081f17208'
		)
	);
	
	protected $response;
	/**
	 * Test that we respond properly to a GET request to /
	 */
	public function testNoMethodGET() {
		$config = Config::get('response.method');

		try {
			$this->call('GET', '/');
		} catch(\Viper\Exception $e) {
			$this->assertEquals($e->getCode(), $config['code']);
			$this->assertEquals($e->getStatusCode(), $config['http']);
			return;
		}

		$this->fail('Exception not thrown');
	}
	/**
	 * Test that wwe respond properly to a POST request to /
	 */
	public function testNoMethodPOST() {
		$config = Config::get('response.method');

		try {
			$this->call('POST', '/');
		} catch(\Viper\Exception $e) {
			$this->assertEquals($e->getCode(), $config['code']);
			$this->assertEquals($e->getStatusCode(), $config['http']);
			return;
		}

		$this->fail('Exception not thrown');
	}
	/**
	 * Test that we respond properly to receiving a request with
	 * incorrect API credentials
	 */
	public function testInvalidCredentials() {
		$config = Config::get('response.api');

		try {
			$this->call('GET', '/user/authorise');
		} catch(\Viper\Exception $e) {
			$this->assertEquals($e->getCode(), $config['code']);
			$this->assertEquals($e->getStatusCode(), $config['http']);
			return;
		}

		$this->fail('Exception not thrown');
	}
	/**
	 * Test that we respond properly to a request with credentials that don't
	 * match our records
	 */
	public function testUnknownGame() {
		$config = Config::get('response.api');
		
		try {
			$this->call('GET', '/user/authorise?key=' . $this->credentials['invalid']['key']);
		} catch(\Viper\Exception $e) {
			$this->assertEquals($e->getCode(), $config['code']);
			$this->assertEquals($e->getStatusCode(), $config['http']);
			return;
		}

		$this->fail('Exception not thrown');
	}
	/**
	 * Test that we respond properly to a request with an invalid user token
	 */
	public function testInvalidToken() {
		$config = Config::get('response.token');
		
		try {
			$this->call('GET', '/user/authorise?key=' . $this->credentials['valid']['key']);
		} catch(\Viper\Exception $e) {
			$this->assertEquals($e->getCode(), $config['code']);
			$this->assertEquals($e->getStatusCode(), $config['http']);
			return;
		}

		$this->fail('Exception not thrown');
	}
	/**
	 * Test that we respond properly to a request that is missing key elements,
	 * ie the signature
	 */
	public function testIncompleteRequest() {
		$config = Config::get('response.incomplete');
		
		try {
			$this->call('POST', '/user/login?key=' . $this->credentials['valid']['key']);
		} catch(\Viper\Exception $e) {
			$this->assertEquals($e->getCode(), $config['code']);
			$this->assertEquals($e->getStatusCode(), $config['http']);
			return;
		}

		$this->fail('Exception not thrown');
	}
	/**
	 * Test that we respond properly to a request with an invalid signature
	 */
	public function testInvalidSignature() {
		$config = Config::get('response.incomplete');
		
		try {
			$this->call('POST', '/user/login?key=' . $this->credentials['valid']['key']);
		} catch(\Viper\Exception $e) {
			$this->assertEquals($e->getCode(), $config['code']);
			$this->assertEquals($e->getStatusCode(), $config['http']);
			return;
		}

		$this->fail('Exception not thrown');
	}
	/**
	 * Test that we response properly to a request using the credentials
	 * of a game that isn't active
	 */
	public function testInactiveGame() {
		$config = Config::get('response.api');
		
		try {
			$this->call('GET', '/user/authorise?key=' . $this->credentials['inactive']['key']);
		} catch(\Viper\Exception $e) {
			$this->assertEquals($e->getCode(), $config['code']);
			$this->assertEquals($e->getStatusCode(), $config['http']);
			return;
		}

		$this->fail('Exception not thrown');
	}
	
}