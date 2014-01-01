<?php

use Viper\Exception as Viper_Exception;

class User_ProfileController extends User_BaseController {
    
    public function get() {
        if($this->user) {
            $profile = $this->user->profile->toArray();
            
            if($profile) {
                return $this->success(array('profile' => $profile));
            }
            
            throw new Viper_Exception('User doesn\'t have a profile', 'unexpected');
        }
        
        throw new Viper_Exception('Invalid token', 'token');
    }
    
    public function edit() {
        if($this->user) {
            $profile = $this->user->profile;
            
            if($profile) {
                $validator = Validator::make(
                    $this->arguments,
                    array(
                        'first_name'    => array(),
                        'last_name'     => array(),
                        'gender'        => array(),
                        'dob'           => array('date')
                    ));
                
                if($validator->fails()) {
                    throw new Viper_Exception($validator->messages, 'validation');
                }
                
                $update = false;
                
                if(isset($this->arguments['first_name']) && !empty($this->arguments['first_name'])) {
                    $profile->first_name = $this->arguments['first_name'];
                    $update = true;
                }
                
                if(isset($this->arguments['last_name']) && !empty($this->arguments['last_name'])) {
                    $profile->last_name = $this->arguments['last_name'];
                    $update = true;
                }
                
                if(isset($this->arguments['gender']) && !empty($this->arguments['gender'])) {
                    if(in_array($this->arguments['gender'], array('m', 'f'))) {
                        $profile->gender = $this->arguments['gender'];
                        $update = true;
                    }
                }
                
                if(isset($this->arguments['dob']) && !empty($this->arguments['dob'])) {
                    $date = strtotime($this->arguments['dob']);
                    
                    if($date) {
                        $profile->dob = date('Y-m-d', $date);
                        $update = true;
                    }
                }
                
                if($update) {
                    $profile->save();
                    
                    return $this->success();
                } else {
                    throw new Viper_Exception('Nothing to update', 'arguments');
                }
            }
            
            throw new Viper_Exception('User doesn\'t have a profle, uh-oh', 'unexpected');
        }
        
        throw new Viper_Exception('Invalid token', 'token');
    }
    
}