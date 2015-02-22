<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;

class User extends Eloquent implements UserInterface {

	public $timestamps = false;
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';
	protected $primaryKey = 'id';
	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('hash', 'token');
	protected $fillable = array('name', 'os', 'token');

	/*
     * returns the friends that the requesting user added
     */
    public function friendsIAdded()
    {
    	return $this->belongsToMany('User', 'friends', 'user_1', 'user_2');
    }

    /*
     * returns the friends that the user got added to (second row)
     */
    public function friendsHeAdded()
    {
    	return $this->belongsToMany('User', 'friends', 'user_2', 'user_1');
    }

    public function getAuthIdentifier()
	{
	    return $this->id;
	}

	/*
	 * since we do not authenticate with a real password that is stored as a hash
	 * we instead only compare tokens. But the eloquent auth driver wants a hased password, so we hash the token
	 */
	public function getAuthPassword()
	{
		return Hash::make($this->token);
	}

	public function getFriendsAttribute()
	{
		if(!array_key_exists('friends', $this->relations)) 
			$this->loadFriends();
	
		return $this->getRelation('friends');
	}

	public function loadFriends()
	{
		$friends = $this->friendsIAdded->intersect($this->friendsHeAdded);
		$this->setRelation('friends', $friends); 
	}

	// functions of UserInterface which are not really used now
	public function getRememberToken(){}

	public function setRememberToken($value){}

	public function getRememberTokenName(){
		return 'token';
	}
}
