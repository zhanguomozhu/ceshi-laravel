<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
use App\Entity\Member;
Route::get('/', function () {
    return view('login');
});

Route::get('/login','View\MemberController@toLogin');

Route::get('/register','View\MemberController@toRegister');

Route::get('/category','View\BookController@toCategory');

Route::get('/product/category_id/{category_id}','View\BookController@toProduct');

Route::get('/product/{product_id}','View\BookController@toPdtContent');

Route::get('/cart','View\CartController@toCart');

Route::get('/pay',function(){
	return view('alipay');
});


//*************************************中间件拦截器 第一种方法***********************//

//Route::get('/cart',['middleware'=>'check.login'],'View\CartController@toCart');

//*************************************中间件拦截器 第二种方法***********************//
Route::group(['middleware'=>'check.login'],function(){

	Route::get('/order_commit/{product_ids}', 'View\OrderController@toOrderCommit');
	Route::get('/order_list', 'View\OrderController@toOrderList');

});





//***********************service****************注册登录相关**************************//
Route::group(['prefix' => 'service'], function () {
	Route::get('validate_code/create','Service\ValidateController@create');
	Route::post('validate_phone/send', 'Service\ValidateController@sendSMS');
	Route::post('validate_email', 'Service\ValidateController@validateEmail');
	Route::post('register', 'Service\MemberController@register');
	Route::post('login', 'Service\MemberController@login');
	Route::get('category/parent_id/{parent_id}', 'Service\BookController@getCategoryByParentId');
	Route::get('cart/add/{product_id}', 'Service\CartController@addCart');
	Route::get('cart/delete', 'Service\CartController@deleteCart');
	Route::post('pay', 'Service\PayController@aliPay');
	Route::post('pay/notify', 'Service\PayController@aliNotify');
	Route::post('pay/call_back', 'Service\PayController@aliCallBack');
	Route::get('pay/merchant', 'Service\PayController@aliMerchant');
});