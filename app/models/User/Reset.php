<?php namespace Viper\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User_Reset extends Eloquent
{

    protected $table = 'users_resets';

    protected $fillable = array(
        'user_id', 'code'
    );
    /**
     * Like with all other models, make sure we don't ever return the id
     * or user_id.
     *
     * @var array
     */
    protected $hidden = array(
        'id', 'user_id', 'user'
    );

    /**
     * Definition of the parent user relationship
     *
     * @return \Viper\Model\User
     */
    public function user()
    {
        return $this->belongsTo('\Viper\Model\User');
    }

    /**
     * Generates a code and then hashes it, giving us the token.
     */
    public function generate()
    {
        if (empty($this->attributes['code'])) {
            do {
                $code = Str::random(8);
            } while (DB::table($this->table)->where('code', $code)->count() != 0);

            $this->attributes['code'] = $code;
        }
    }

}