<?php

function errorMessage($msg, $logout = false)
{
	if($logout)
		return Response::json(array('error_msg' => $msg, 'logout' => 1));
	else
		return Response::json(array('error_msg' => $msg));
}

function isValidUtf8String($string, $maxLength, $allowNewlines = false)
{
    if (empty($string) || strlen($string) > $maxLength)
        return false;

    if (mb_check_encoding($string, 'UTF-8') === false)
        return false;

    // Don't allow control characters, except possibly newlines 
    for ($t = 0; $t < strlen($string); $t++)
    {
        $ord = ord($string{$t});

        if ($allowNewlines && ($ord == 10 || $ord == 13))
            continue;

        if ($ord < 32)
            return false;
    }

    return true;
}

class ApiController extends BaseController {

	// login errors
    const LOGIN_SUCCESS = 1;
    const LOGIN_USERNAME_NOT_FOUND = 2;
    const LOGIN_PASSWORD_WRONG = 3;

    // attributes
    static $ATTR_LOGIN = array('username', 'password', 'os', 'token');
    static $ATTR_USER = array('id', 'token');
    static $ATTR_USER_FRIEND = array('id', 'token', 'friend_name');

    const MAX_USERNAME_LENGTH = 20;
    private $pdo;

    private $loginArr;

    function correctPostAttributes($attributes)
    {
    	foreach($attributes as $attr) {
    		if(!Input::has($attr))
    			return false;
    	}
    	return true;
    }

    function credentialsValid($id, $token) {
    	$user = User::find($id);

    	if ($user->token === $token) {
    		return true;
    	} else {
    		return false;
    	}
    }

    /*
     * Signup
     *
     * @input (string) username 
     * @input (string) password
     * @input (string) os the operating system
     * @input (string) token
     * 
     * if succesful
     * @returnJson (int) 'id' if successful
     * if not
     * @returnJson (string) 'error_msg'
	 */
	public function postSignup()
    {
        // try to login first, if not successful assume that not yet registered
        // if successful, then normal login params are returned
        $loginAttempt = $this->login();
  
        if($loginAttempt == self::LOGIN_SUCCESS) {
        	Log::info('login_success');
            return Response::json($this->loginArr);
        } else if($loginAttempt == self::LOGIN_PASSWORD_WRONG) {
            // means username already exists
            Log::info('login_failed');
            return errorMessage('Username already exists');
        }

        $userName = Input::get('username');
        $userPwd = Input::get('password');

        // check if they are valid
        if(!isValidUtf8String($userName, 20) || !isValidUtf8String($userPwd , 255))
        	return errorMessage('invalid username or password');
        // @TODO rules for password and username (length, ...)

        // hash the password
        $hash = Hash::make($userPwd);
        // TODO add token and ios
        $user = new User;
        $user->name = $userName;
        $user->hash = $hash;
        $user->os = Input::get('os');
        $user->token = Input::get('token');

        $user->save();

        return Response::json(array('id'=> $user->id));
    }

    /*
     * Login
     *
     * @input (string) username 
     * @input (string) password
	 * @input (string) os the operating system
     * @input (string) token
     *
     * if succesful
     * @returnJson (int) 'id' if successful
     * if not
     * @returnJson (string) 'error_msg'
	 */
    public function postLogin() 
    {   
        $success = $this->login();

        if ($success === self::LOGIN_SUCCESS) {
            return Response::json($this->loginArr);
        } else {
            return errorMessage("Wrong username or password");
        }
    }

    function login()
    {
    	try {
	    	$userName = Input::get('username');
	    	$userPwd = Input::get('password');

	    	$user = User::where('name', $userName)->first();

	        if(empty($user)) return self::LOGIN_USERNAME_NOT_FOUND;

	        if(Hash::check($userPwd, $user->hash)) {
	        	$this->loginArr = array('id' => $user->id);
	        	// renew token and ios
	        	$user->token = Input::get('token');
	        	$user->os = Input::get('os');
	        	$user->save();
	        	return self::LOGIN_SUCCESS;
	        } else
	        	return self::LOGIN_PASSWORD_WRONG;
	    } catch (Exception $exception) {
	    	Log::error($exception);
	    	return self::LOGIN_PASSWORD_WRONG;
	    }
    }

    /*
     * Add
     * Add new friend and check if friendship is mutual
     *
     * @input (string) token
     * @input (int) id 
     * @input (string) friend_name
     * 
     * if succesful
     * 	@returnJson (int) 'status' 0=pending, 1=friend added(mutually)
     * else
     *  @returnJson (string) 'error_msg'
     *  @returnJson (int) 'logout' = 1 if the app has to logout the user
	 */
    public function postAdd()
    {
    	$user = User::find(Input::get('id'));
    	$friendName = Input::get('friend_name');

		$friendIds = User::where('name', $friendName)->lists('id');

    	if(empty($friendIds)) 
    		return errorMessage('username not found');
    
    	// create friendship if not already there i.e. collection isEmpty
    	if($user->friendsIAdded()->wherePivot('user_2', $friendIds[0])->get()->isEmpty()){
    		$user->friendsIAdded()->attach($friendIds[0]);
    	}

    	// check if mutual friendship
    	if(!$user->friendsHeAdded()->wherePivot('user_1', $friendIds[0])->get()->isEmpty()) {
    		// friendship is mutual
    		return array('status' => 1);
    	} else {
    		// friendship is not mutual
    		return array('status' => 0);
    	}
    }
    /*
     * Login
     *
     * @input (string) token
     * @input (int) id 
     * @input (string) friend_name
     * 
     * if succesful
     *  @returnJson (int) 'success' 1=success
     * else
     *  @returnJson (string) 'error_msg'
     *  @returnJson (int) 'logout' = 1 if the app has to logout the user
     */
    public function postRemove()
    {
    	$user = User::find(Input::get('id'));
    	$friendName = Input::get('friend_name');

    	// returns array
    	$friendIds = User::where('name', $friendName)->lists('id');
   		
   		if(empty($friendIds)) 
    		return errorMessage('username not found');

    	$user->friendsIAdded()->detach($friendIds[0]);

    	return array('success' => 1);
    }
    /*
     * Gong
     *
     * @input (string) token
     * @input (int) id 
     * 
     * if succesful
     *  @returnJson (int) 'success' 1=success
     * else
     *  @returnJson (string) 'error_msg'
     *  @returnJson (int) 'logout' = 1 if the app has to logout the user
     */
    public function postGong()
    {
    	$user = User::find(Input::get('id'));

    	// select all friends
        //$friends = $user->friends;    // would also work but is slower
        $friends = DB::table('users')
            ->join('friends AS f1', function($join)
                {
                    $join->on('users.id', '=', 'f1.user_1')
                         ->where('f1.user_2', '=', Input::get('id'));
                })
            ->join('friends AS f2', function($join)
                {
                    $join->on('users.id', '=', 'f2.user_2')
                         ->where('f2.user_1', '=', Input::get('id'));
                })
            ->select('users.id')
            ->get();
        
        // insert all ids into an array to insert
        $inserts = array();
        $userName = $user->name;        

        foreach($friends as $friend) {
            $inserts[] = array('user_id'=> $friend->id, 'friend_name' => $userName);
        }

	    // insert array
	    try {
	        DB::table('notification_queue')->insert($inserts);
        } catch (Exception $e) {
       		Log::error($e);
       		return Response::json(array('error_msg' => 'database insert error'));
  		}

	    // return success, otherwise 
        return Response::json(array('success' => 1));
    }

    /*
     * Update
     *
     * @input (string) token
     * @input (int) id 
     * @input (string) friend_name
     * 
     * if success
     *  @returnJson (array) where each element is
     *       (array) 'name', 'status' = 0 if friend requests friendship, 1 if friendship is mutual
     *
     * else
     *  @returnJson (string) 'error_msg'
     *  @returnJson (int) 'logout' = 1 if the app has to logout the user
     */
    public function postUpdate()
    {
    	$id = Input::get('id');

        // @Todo maybe check if id matches a user?

        $friends = DB::table('users')
        ->join('friends AS f1', function($join)
        {
            $join->on('users.id', '=', 'f1.user_1')
                 ->where('f1.user_2', '=', Input::get('id'));
        })
        ->leftJoin('friends AS f2', function($join)
        {
            $join->on('users.id', '=', 'f2.user_2')
                 ->where('f2.user_1', '=', Input::get('id'));
        })
        ->select('users.name', 'f2.user_2')
        ->get();

        $result = array();
        foreach($friends as $friend) {
            $result[] = array('name' => $friend->name, 
                    'status' => (empty($friend->user_2)) ? 0 : 1);
        }
        
        return Response::json($result);
    }

    public function returnErrorMessage($msg) 
    {
    	return json_encode($msg);
    }

    public function logout()
    {
    	return Response::json(array('error_msg' => 'wrong token', 'logout'=> 1));
    }

    public function wrongParameters()
    {
    	return Response::json(array('error_msg' => 'wrong input parameters'));
    }

    public function missingMethod($parameters = array())
	{
		return Response::json(array('error_msg' => 'command not found'));
	}
}
