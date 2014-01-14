<?php

class UserTest extends TestCase
{

    protected $credentials = array(
        'valid' => array(
            'key' => '917e3d32272adedc65985dcb45d37168',
            'secret' => '6a1b5d00488f07bf333df2f7700e8222d05e98cc2ba84cc31f9c1977cfdfd77f'
        ),
        'invalid' => array(
            'key' => '917e3d32272adedc65985dcb45d3716'
        ),
        'inactive' => array(
            'key' => '6f8d35b5f4f2fe2d189ae56721fb58e8',
            'secret' => 'c0c8bc15912cd0abeaeef744f5881297c2854fcf6d806208bc5d331081f17208'
        )
    );

    protected $token = null;

    public function testLoginMissingData()
    {
        $uri = '/user/login?key=' . $this->credentials['valid']['key'];
        $arguments = json_encode(array(
            'username' => 'testuser'
        ));
        $signature = hash_hmac('sha1', $arguments, $this->credentials['valid']['secret'], false);

        try {
            $this->call('POST', $uri, array(
                'arguments' => $arguments,
                'signature' => $signature
            ));
        } catch (\Viper\Exception $e) {
            $config = Config::get('response.validation');
            $this->assertEquals($e->getCode(), $config['code']);
            $this->assertEquals($e->getStatusCode(), $config['http']);
            return;
        }
    }

    public function testLoginIncorrectPassword()
    {
        $uri = '/user/login?key=' . $this->credentials['valid']['key'];
        $arguments = json_encode(array(
            'username' => 'testuser',
            'password' => 'lol'
        ));
        $signature = hash_hmac('sha1', $arguments, $this->credentials['valid']['secret'], false);

        try {
            $this->call('POST', $uri, array(
                'arguments' => $arguments,
                'signature' => $signature
            ));
        } catch (\Viper\Exception $e) {
            $config = Config::get('response.argument');
            $this->assertEquals($e->getCode(), $config['code']);
            $this->assertEquals($e->getStatusCode(), $config['http']);
            return;
        }
    }

    public function testLoginValid()
    {
        $uri = '/user/login?key=' . $this->credentials['valid']['key'];
        $arguments = json_encode(array(
            'username' => 'testuser',
            'password' => 'password'
        ));
        $signature = hash_hmac('sha1', $arguments, $this->credentials['valid']['secret'], false);

        $response = $this->call('POST', $uri, array(
            'arguments' => $arguments,
            'signature' => $signature
        ));
        $content = $response->getContent();
        $clean_content = json_decode($content, true);

        $this->assertTrue(is_array($clean_content));
        $this->assertEquals($clean_content['status'], 'success');
        $this->assertTrue(array_key_exists('user', $clean_content['data']));
        $this->assertEquals($clean_content['data']['user']['username'], 'testuser');
    }

    public function testLoginValidUsingWith()
    {
        $uri = '/user/login?key=' . $this->credentials['valid']['key'];
        $arguments = json_encode(array(
            'username' => 'testuser',
            'password' => 'password',
            'with' => 'profile'
        ));
        $signature = hash_hmac('sha1', $arguments, $this->credentials['valid']['secret'], false);

        $response = $this->call('POST', $uri, array(
            'arguments' => $arguments,
            'signature' => $signature
        ));
        $content = $response->getContent();
        $clean_content = json_decode($content, true);

        $this->assertTrue(is_array($clean_content));
        $this->assertEquals($clean_content['status'], 'success');
        $this->assertTrue(array_key_exists('user', $clean_content['data']));
        $this->assertEquals($clean_content['data']['user']['username'], 'testuser');
        $this->token = $clean_content['data']['user']['token'];
    }

    public function testAuthoriseValidToken()
    {
        $uri = '/user/login?key=' . $this->credentials['valid']['key'];
        $arguments = json_encode(array(
            'username' => 'testuser',
            'password' => 'password',
            'with' => 'profile'
        ));
        $signature = hash_hmac('sha1', $arguments, $this->credentials['valid']['secret'], false);

        $response = $this->call('POST', $uri, array(
            'arguments' => $arguments,
            'signature' => $signature
        ));
        $content = $response->getContent();
        $clean_content = json_decode($content, true);

        $token = $clean_content['data']['user']['token'];

        $uri = '/user/authorise?key=' . $this->credentials['valid']['key'];
        $arguments = array(
            'token' => $token,
            'with' => 'profile,gamedata'
        );

        $response = $this->call('GET', $uri, $arguments);
        $content = $response->getContent();
        $clean_content = json_decode($content, true);

        $this->assertTrue(is_array($clean_content));
        $this->assertEquals($clean_content['status'], 'success');
        $this->assertTrue(array_key_exists('user', $clean_content['data']));
        $this->assertEquals($clean_content['data']['user']['username'], 'testuser');
    }


}