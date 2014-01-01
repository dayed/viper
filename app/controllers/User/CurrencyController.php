<?php

use Viper\Exception as Viper_Exception;

class User_CurrencyController extends User_BaseController {
    
    public function wallet() {
        if($this->user && $this->game) {
            $currencies = $this->user->wallet()->forGame($this->game->id);
            
            if($currencies) {
                
            }
        }
    }
    
}