<?php

class Admin_BaseController extends Controller
{

    protected $admin;

    protected $permissions;

    public function __construct()
    {
        if (Auth::check()) {
            $this->admin = Auth::user();
        }
    }

    public function _isSignedIn()
    {
        return !empty($this->admin);
    }

    protected function setupLayout()
    {
        if (!is_null($this->layout)) {
            $this->layout = View::make($this->layout);
        }
    }

    protected function getLayout($content, $data = array())
    {
        if ($this->layout) {
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    View::share($key, $value);
                }
            }
            $this->layout->content_view = $content;

            return $this->layout;
        }
    }

}