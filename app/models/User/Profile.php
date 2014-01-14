<?php namespace Viper\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;

class User_Profile extends Eloquent
{

    protected $table = 'users_profiles';

    protected $fillable = array(
        'user_id', 'first_name', 'last_name', 'gender', 'dob'
    );
    /**
     * Make sure we hide the id and user_id as we don't ever need to display
     * those. We also hide gender in favour of some nice boolean attributes.
     *
     * @var array
     */
    protected $hidden = array(
        'id', 'user_id', 'gender'
    );
    /**
     * Just for ease, we provide some nice boolean values so that the gender
     * can be easily identified.
     *
     * @var array
     */
    protected $appends = array(
        'name', 'is_male', 'is_female'
    );

    /**
     * Defines the owner of the profile
     *
     * @return \Viper\Model\User
     */
    public function user()
    {
        return $this->belongsTo('\Viper\Model\User');
    }

    /**
     * For ease, we return the entire name so that developers don't have to manually
     * concatenate the values.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->attributes['first_name'] . ' ' . $this->attributes['last_name'];
    }

    /**
     * Returns true or false depending on whether or not the gender is male.
     *
     * @return boolean
     */
    public function getIsMaleAttribute()
    {
        return ($this->attributes['gender'] == 'm');
    }

    /**
     * Returns true or false depending on whether or not the gender is female.
     *
     * @return boolean
     */
    public function getIsFemaleAttribute()
    {
        return ($this->attributes['gender'] == 'f');
    }

}