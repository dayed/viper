<?php

use Viper\Exception as Viper_Exception;

class User_ProfileController extends User_BaseController
{
    /**
     * This is an easy method to get the users profiles. If no user is logged in,
     * we throw an exception which theoretically never gets called, but still, better
     * safe than sorry.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Viper\Exception
     * @ApiDoc()
     */
    public function get()
    {
        if ($this->user) {
            $profile = $this->user->profile->toArray();

            if ($profile) {
                return $this->success(array('profile' => $profile));
            }
            /**
             * Some of you may be inclined to have this create the profile and then return it,
             * but the fact that the user doesn't have a profile is an indicator of a much larger
             * problem that we don't just want to sweep under the rug. Unless of course you
             * like potentially catastrophic errors that can disrupt everything.
             */
            throw new Viper_Exception('User doesn\'t have a profile', 'unexpected');
        }

        throw new Viper_Exception('Invalid token', 'token');
    }

    /**
     * Since users are all about being in control of their own stuff, this method
     * facilitates such a requirement, by allowing them to edit their profile.
     *
     * @todo Add support for custom profile formats.
     * @return \Illuminate\Http\JsonResponse
     * @throws Viper\Exception
     */
    public function edit()
    {
        if ($this->user) {
            $profile = $this->user->profile;

            if ($profile) {
                /**
                 * @todo Add rules for profile data.
                 */
                $validator = Validator::make(
                    $this->arguments,
                    array(
                        'first_name' => array(),
                        'last_name' => array(),
                        'gender' => array(),
                        'dob' => array('date')
                    ));

                if ($validator->fails()) {
                    throw new Viper_Exception($validator->messages, 'validation');
                }
                /**
                 * Although the fields aren't required, we obviously don't want to start writing to
                 * a database if we have no data to write to it, so we have a boolean to handle
                 * whether or not we need to call a save()
                 */
                $update = false;

                if (isset($this->arguments['first_name']) && !empty($this->arguments['first_name'])) {
                    $profile->first_name = $this->arguments['first_name'];
                    $update = true;
                }

                if (isset($this->arguments['last_name']) && !empty($this->arguments['last_name'])) {
                    $profile->last_name = $this->arguments['last_name'];
                    $update = true;
                }

                if (isset($this->arguments['gender']) && !empty($this->arguments['gender'])) {
                    if (in_array($this->arguments['gender'], array('m', 'f'))) {
                        $profile->gender = $this->arguments['gender'];
                        $update = true;
                    }
                }

                if (isset($this->arguments['dob']) && !empty($this->arguments['dob'])) {
                    $date = strtotime($this->arguments['dob']);

                    if ($date) {
                        $profile->dob = date('Y-m-d', $date);
                        $update = true;
                    }
                }

                if ($update) {
                    /**
                     * We required an update, now save and return a blank success.
                     */
                    $profile->save();
                    /**
                     * We return a blank success because there's no need to return information to the
                     * origin point of the information anyway, think about it!
                     */
                    return $this->success();
                } else {
                    /**
                     * We don't want developers get lazing, so this is here to enforce some sort of
                     * integrity check and validation, to save bandwidth and cpu cycles.
                     */
                    throw new Viper_Exception('Nothing to update', 'arguments');
                }
            }
            /**
             * The same conditions and reasoning apply here as they do in the get method above.
             * The user doesn't have a profile, therefore something went horribly wrong, somewhere,
             * at sometime, in some place.
             */
            throw new Viper_Exception('User doesn\'t have a profle, uh-oh', 'unexpected');
        }
        /**
         * Another mythical throw point.
         */
        throw new Viper_Exception('Invalid token', 'token');
    }

}