<?php

// Custom Filters

/* 
 * login filter
 * filters all events that do not have the right input parameters for a login attempt
 */
Route::filter('login', function()
{
	Log::info('login attempt filter');
	// check if name/token/os/password
	if(!Input::has('username') || !Input::has('token') || !Input::has('os') || !Input::has('password')) {
		return Redirect::to('api/wrongParameters');
	}
});

/* 
 * user_auth filter
 *
 * filters all events with wrong input parameters and
 * filters all events where a token does not match. 
 * Device must be logged out to regain a new token
 */
Route::filter('user_auth', function()
{
	// check if id/token
	if(!Input::has('id') || !Input::has('token'))
		return Redirect::to('api/wrongParameters');

	$user = User::find(Input::get('id'));

	// TODO: user is null
	if($user->token !== Input::get('token'))
		return Redirect::to('api/logout');
});

Route::filter('auth_custom', function()
{
	if(!Input::has('id') || !Input::has('token'))
		return Redirect::to('api/wrongParameters');
	// not really the password, but in User Model getPassword returns token
	if(!Auth::attempt(array('id'=> Input::get('id'), 'password' => Input::get('token')))) {
		return Redirect::to('api/logout');
	}
});

/* 
 * login filter
 * filters all events that do not have the right input parametrs for a friendship change
 */
Route::filter('friend', function(){
	// check if id/token
	if(!Input::has('friend_name'))
		return Redirect::to('api/wrongParameters');
});

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
	//
});


App::after(function($request, $response)
{
	//
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth', function()
{
	if (Auth::guest())
	{
		if (Request::ajax())
		{
			return Response::make('Unauthorized', 401);
		}
		else
		{
			return Redirect::guest('login');
		}
	}
});


Route::filter('auth.basic', function()
{
	return Auth::basic();
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
	if (Auth::check()) return Redirect::to('/');
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
	if (Session::token() !== Input::get('_token'))
	{
		throw new Illuminate\Session\TokenMismatchException;
	}
});
