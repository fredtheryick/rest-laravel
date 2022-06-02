<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

/**
 * @group Account Management
 *
 * APIs for managing users account
 */
class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        
    }
    
    /**
     * Create User
     * 
     * <aside class="notice">This endpoint lets you create a user.</aside>
     * @unauthenticated
     * 
     * @header Content-Type application/json
     * 
     * @bodyParam name string required Fill with user fullname. Example: Kevin Ringo
     * @bodyParam email string required Fill with user email; email must unique and can only be used once. Example: kevin@gmail.com
     * @bodyParam phone number required Fill with user phone number; phone number must unique and can only be used once. Example: 81234567890
     * @bodyParam password string required Fill with user password; must consist alphanumeric, uppercase, lowercase; min 8 characters. Example: 1234qweR
     * 
     * @response status=200 scenario=success {
     *  "access_token": "{YOUR_AUTH_TOKEN}",
     *  "token_type": "bearer",
     *  "expires_in": 3600
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "success": false,
     *  "message": "Input is not valid!",
     *  "errors": {
     *      "email": [
     *          "validation.unique"
     *      ],
     *      "phone": [
     *          "validation.unique"
     *      ]
     *  }
     * }
     * 
     */
    public function register(Request $request) {
        $data = $request->all();
        
        $rules  = array(
            'name'      => 'required|min:5|max:60',
            'email'     => 'required|unique:users|email',
            'phone'     => 'required|unique:users|digits_between:9,15',
            'password'  => '|min:8|max:50|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
        );
        
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            $user = new User();
            $user->role = 2;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->phone = $request->input('phone');
            $user->password = Hash::make($request->input('password'));
            $user->save();
            
            $credentials = $request->only('email', 'password');
            
            if (! $token = Auth::attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $user->last_login = date('Y-m-d H:i:s');
            $user->save();
            
            return $this->respondWithToken($token);
        }
        
        return response()->json([
            'success'   => false,
            'message'   => 'Input is not valid!',
            'errors'    => $validator->errors()
        ], 400);
    }
    
    /**
     * Login into Application
     * 
     * <aside class="notice">This endpoint lets you login to the application.</aside>
     * @unauthenticated
     * 
     * @header Content-Type application/json
     * 
     * @bodyParam email string required Fill with your registered email. Example: kevin@gmail.com
     * @bodyParam password string required Fill with your registered password; must consist alphanumeric, uppercase, lowercase; min 8 characters. Example: 1234qweR
     * 
     * @response status=200 scenario=success {
     *  "access_token": "{YOUR_AUTH_TOKEN}",
     *  "token_type": "bearer",
     *  "expires_in": 3600
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "error": "Unauthorized",
     * }
     * 
     */
    public function login()
    {
        $credentials = request(['email', 'password']);
        
        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Auth::user()->last_login = date('Y-m-d H:i:s');
        Auth::user()->save();
        
        return $this->respondWithToken($token);
    }
    
    /**
     * Login Data
     * 
     * Get login data of the authenticated user.
     * <aside class="notice">This endpoint lets you display your login data.</aside>
     * @authenticated
     * 
     * @header Content-Type application/json
     * @header Authorization Bearer {{token}}
     * 
     * @response status=200 scenario=success {
     *  "name": "Osrin Ringo",
     *  "email": "osrin@gmail.com",
     *  "phone": "81234567899",
     *  "last_login": "2022-06-01 23:37:12"
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "status": "Token is Invalid!"
     * }
     *
     */
    public function loginData()
    {
        $user = Auth::userOrFail();
        
        return response()->json([
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => $user->phone,
            'last_login'    => date('Y-m-d H:i:s', strtotime($user->last_login))
        ], 200);
    }
    
    /**
     * Logout
     * 
     * Log the user out.
     * <aside class="notice">This endpoint lets you invalidate your token.</aside>
     * @unauthenticated
     * 
     * @header Content-Type application/json
     * @queryParam token string required YOUR_AUTH_TOKEN. Example: {YOUR_AUTH_TOKEN}
     * 
     * @response status=200 scenario=success {
     *  "message": "Successfully logged out!",
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "status": "Authorization Token not found!"
     * }
     *
     */
    public function logout()
    {
        Auth::logout();
        
        return response()->json(['message' => 'Successfully logged out!']);
    }
    
    /**
     * Refresh Token
     * 
     * <aside class="notice">This endpoint lets you refresh your token.</aside>
     * @unauthenticated
     * 
     * @header Content-Type application/json
     * @queryParam token string required YOUR_AUTH_TOKEN. Example: {YOUR_AUTH_TOKEN}
     * 
     * @response status=200 scenario=success {
     *  "access_token": "{YOUR_AUTH_TOKEN}",
     *  "token_type": "bearer",
     *  "expires_in": 3600
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "status": "Token is Invalid!"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }
    
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ]);
    }
}
