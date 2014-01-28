<?php

class AdminController extends Admin_BaseController
{

    public function signin()
    {
        if (Input::has('admin_signin')) {
            $arguments = Input::only(array(
                'email',
                'password'
            ));
            $remember = Input::get('remember');
            $validator = Validator::make($arguments, array(
                'email' => array('required', 'exists:admins'),
                'password' => array('required')
            ));

            if ($validator->fails()) {
                return Redirect::route('admin.signin')->withErrors($validator);
            }

            $credentials = array_merge($arguments, array('active' => 1));

            if (Auth::attempt($credentials, ($remember == 'yes'))) {
                return Redirect::route('admin.dashboard');
            }
        }

        return $this->getLayout('admin.signin');
    }

    public function signout()
    {
        if ($this->_isSignedIn()) {
            Auth::signout();

            return Redirect::route('admin.signin')->with('error_message', 'Successfully signed out');
        }

        return Redirect::route('admin.signin');
    }

}