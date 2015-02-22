<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
	return View::make('hello');
});

        // $sql = "SELECT ul.id FROM"
        // . "( SELECT U.id, U.user_name FROM users U, friends F"
        // . " WHERE F.user_1 = ? AND F.user_2 = U.id"
        // . ") ul INNER JOIN"
        // . "( SELECT U.id FROM users U, friends F"
        // . " WHERE F.user_1 = U.id AND F.user_2 = ?"
        // . ") ur" 
        // . " ON ul.id = ur.id";
Route::any('user', function()
{
    $userId = 1;

    $friends = DB::table('users')
        ->join('friends AS f1', function($join)
        {
            $join->on('users.id', '=', 'f1.user_1')
                 ->where('f1.user_2', '=', $userId);
        })
        ->leftJoin('friends AS f2', function($join)
        {
            $join->on('users.id', '=', 'f2.user_2')
                 ->where('f2.user_1', '=', 1);
        })
        ->select('users.id', 'users.name', 'f2.user_2')
        ->get();
    // echo print_r($friends,true);
    foreach($friends as $friend) {
        if(empty($friend->user_2))
            echo $friend->name . ' ' . $friend->id . '<br>';
        else 
            echo $friend->name . ' ' . $friend->id . 'mutual<br>';
    }
    // foreach($friends as $user) {
    //     echo print_r($user->name, true);
    // }

    //return View::make('test');
});
//Route::post('api/signup', 'ApiController@postSignup');
Route::group(array('before' => 'login'), function()
{
    Route::post('api/login', 'ApiController@postLogin');
    Route::post('api/signup', 'ApiController@postSignup');
});

Route::group(array('before' => 'auth_custom'), function()
{
    Route::group(array('before' => 'friend'), function()
    {
        Route::post('api/add', 'ApiController@postAdd');
        Route::post('api/remove', 'ApiController@postRemove');
    });

    Route::post('api/gong', 'ApiController@postGong');
    Route::post('api/update', 'ApiController@postUpdate');
});

Route::any('api/logout', 'ApiController@logout');
Route::any('api/wrongParameters', 'ApiController@wrongParameters');

