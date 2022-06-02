<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Customer;
use App\Models\User;

/**
 * @group Customer Management
 *
 * APIs for managing customers
 */
class CustomerController extends Controller
{
    /**
     * Create a new CustomerController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('jwt.verify');
    }
    
    /**
     * List Customer
     * 
     * Can only be accessed by Super Administrator.
     * <aside class="notice">This endpoint lets you display list of customer and do a search by address or point.</aside>
     * @authenticated
     * 
     * @header Content-Type application/json
     * @header Authorization Bearer {{token}}
     * @queryParam address string Fill this with customer address to search by address. Example: Taman Anggrek
     * @queryParam point number Fill this with number to search by point (query used >=). Example: 200
     * 
     * @response status=200 scenario=success {
     *  "success": true,
     *  "list": {
     *      "current_page": 1,
     *      "data": [
     *          {
     *              "id": 1,
     *              "user_id": 4,
     *              "address": "Jakarta Selatan\r\nDKI Jakarta",
     *              "point": "250.00",
     *              "created_at": "2022-06-02T01:05:10.000000Z",
     *              "updated_at": "2022-06-02T01:05:10.000000Z",
     *              "deleted_at": null
     *          }
     *      ],
     *      "first_page_url": "http://localhost/rest-laravel/public/api/customer?page=1",
     *      "from": 1,
     *      "last_page": 1,
     *      "last_page_url": "http://localhost/rest-laravel/public/api/customer?page=1",
     *      "links": [
     *          {
     *              "url": null,
     *              "label": "pagination.previous",
     *              "active": false
     *           },
     *           {
     *              "url": "http://localhost/rest-laravel/public/api/customer?page=1",
     *              "label": "1",
     *              "active": true
     *           },
     *           {
     *              "url": null,
     *              "label": "pagination.next",
     *              "active": false
     *           }
     *      ],
     *      "next_page_url": null,
     *      "path": "http://localhost/rest-laravel/public/api/customer",
     *      "per_page": 15,
     *      "prev_page_url": null,
     *      "to": 2,
     *      "total": 2
     *  }
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "success": false,
     *  "message": "Something went wrong!"
     * }
     *
     */
    public function index(Request $request)
    {
        if(Auth::user()->role == 1) {
            $list = Customer::select('*')
                ->addressLike($request->input('address'))
                ->pointLike($request->input('point'))
                ->paginate();
            
            return response()->json([
                'success'   => true,
                'list'      => $list
            ], 200);
        }
        
        return response()->json([
            'success'   => false,
            'message'   => "Access Denied!"
        ], 400);
    }

    /**
     * Create Customer
     * 
     * Can only be accessed by Super Administrator.
     * <aside class="notice">This endpoint lets you create a customer.</aside>
     * @authenticated
     * 
     * @header Content-Type application/json
     * @header Authorization Bearer {{token}}
     * 
     * @bodyParam user_id string required Fill with user ID with role is 2 (Customer). Example: 5
     * @bodyParam address string required Fill with customer's address. Example: Taman Anggrek, Jakarta Barat
     * @bodyParam point number required Fill with customer's point. Example: 100
     * 
     * @response status=200 scenario=success {
     *  "success": true,
     *  "message": "Success create customer!",
     *  "data": {
     *      "user_id": 5,
     *      "address": "Taman Anggrek, Jakarta Barat",
     *      "point": 100
     *  }
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "success": false,
     *  "message": "Input is not valid!",
     *  "errors": {
     *      "user_id": [
     *          "validation.in"
     *      ]
     *  }
     * }
     * 
     */
    public function store(Request $request)
    {
        if(Auth::user()->role == 1) {
            $users = User::select('id')
                ->where('role', 2)
                ->get();
            
            $arrayUserID = array();
            foreach($users as $user) {
                array_push($arrayUserID, $user->id);
            }
            
            $data = $request->all();
            
            $rules  = array(
                'user_id'   => 'required|unique:customers|' . Rule::in($arrayUserID),
                'address'   => 'required|string',
                'point'     => 'required|numeric',
            );
            
            $validator = Validator::make($data, $rules);
            if ($validator->passes()) {
                $customer = new Customer();
                $customer->user_id = $request->input('user_id');
                $customer->address = $request->input('address');
                $customer->point = $request->input('point');
                $customer->save();
                
                return response()->json([
                    'success'   => true,
                    'message'   => 'Success create customer!',
                    'data'      => $data
                ], 200);
            }
            
            return response()->json([
                'success'   => false,
                'message'   => 'Input is not valid!',
                'errors'    => $validator->errors()
            ], 400);
        }
        
        return response()->json([
            'success'   => false,
            'message'   => "Access Denied!"
        ], 400);
    }

    /**
     * Display Specified Customer
     * 
     * Can only be accessed by Super Administrator or User logged in.
     * <aside class="notice">This endpoint lets you display detail data of specified customer.</aside>
     * @authenticated
     * 
     * @header Content-Type application/json
     * @header Authorization Bearer {{token}}
     * 
     * @response status=200 scenario=success {
     *  "success": true,
     *  "customer": {
     *      "address": "Jakarta Selatan\r\nDKI Jakarta",
     *      "point": "250.00"
     *  }
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "success": false,
     *  "message": "Something went wrong!"
     * }
     *
     */
    public function show($id)
    {
        if(Auth::user()->role == 1) {
            $customer = Customer::select('*')
                ->where('user_id', $id)
                ->first();
            
            return response()->json([
                'success'   => true,
                'customer'  => $customer
            ], 200);
        }
        
        if(Auth::user()->role == 2 && Auth::user()->id == $id) {
            $customer = Customer::select('address', 'point')
                ->where('user_id', $id)
                ->first();
            
            return response()->json([
                'success'   => true,
                'customer'  => $customer
            ], 200);
        }
        
        return response()->json([
            'success'   => false,
            'message'   => "Access Denied!"
        ], 400);
    }

    /**
     * Update Specified Customer
     * 
     * Can only be accessed by Super Administrator or User logged in.
     * <aside class="notice">This endpoint lets you update detail data of specified customer.</aside>
     * @authenticated
     * 
     * @header Content-Type application/json
     * @header Authorization Bearer {{token}}
     * 
     * @bodyParam address string required Fill with customer's address. Example: Jalan Sana Sini, Jakarta Selatan
     * @bodyParam point number required Fill with customer's point; Accessible by Super Administrator. Example: 200
     * 
     * @response status=200 scenario=success {
     *  "success": true,
     *  "message": "Success update detail customer!",
     *  "data": {
     *      "address": "Jalan Sana Sini, Jakarta Selatan",
     *      "point": 200
     *  }
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "success": false,
     *  "message": "Input is not valid!",
     *  "errors": {
     *      "point": [
     *          "validation.numeric"
     *      ]
     *  }
     * }
     *
     */
    public function update(Request $request, $id)
    {
        if(Auth::user()->role == 1 || (Auth::user()->role == 2 && Auth::user()->id == $id)) {
            $customer = Customer::where('user_id', $id)
                ->first();
            
            $data = $request->all();
            
            if(Auth::user()->role == 1) {
                $rules  = array(
                    'address'   => 'required|string',
                    'point'     => 'required|numeric',
                );
            }
            
            if(Auth::user()->role == 2 && Auth::user()->id == $id) {
                $rules  = array(
                    'address'   => 'required|string',
                );
            }
            
            $validator = Validator::make($data, $rules);
            if ($validator->passes()) {
                $customer->address = $request->input('address');
                $customer->point = Auth::user()->role == 1 ? $request->input('point') : $customer->point;
                $customer->save();
                
                return response()->json([
                    'success'   => true,
                    'message'   => 'Success update detail customer!',
                    'data'      => $data
                ], 200);
            }
            
            return response()->json([
                'success'   => false,
                'message'   => 'Input is not valid!',
                'errors'    => $validator->errors()
            ], 400);
        }
        
        return response()->json([
            'success'   => false,
            'message'   => "Access Denied!"
        ], 400);
    }

    /**
     * Delete Specified Customer
     * 
     * Can only be accessed by Super Administrator.
     * <aside class="notice">This endpoint lets you delete specified customer.</aside>
     * @authenticated
     * 
     * @header Content-Type application/json
     * @header Authorization Bearer {{token}}
     * 
     * @bodyParam confirmation string required Fill with CONFIRM DELETE if you are sure to delete. Example: CONFIRM DELETE
     * 
     * @response status=200 scenario=success {
     *  "success": true,
     *  "message": "Success delete customer!"
     * }
     * 
     * @response status=400 scenario="failed" {
     *  "success": false,
     *  "message": "Only accept CONFIRM DELETE!"
     * }
     *
     */
    public function destroy(Request $request, $id)
    {
        if(Auth::user()->role == 1) {
            $customer = Customer::where('user_id', $id)
                ->first();
            
            if(!$customer) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'There is no data!',
                ], 400);
            }
            
            $data = $request->all();
            
            $rules  = array(
                'confirmation' => 'required|string',
            );
            
            $validator = Validator::make($data, $rules);
            if ($validator->passes()) {
                if($request->input('confirmation') === "CONFIRM DELETE") {
                    $customer->delete();
                    
                    return response()->json([
                        'success'   => true,
                        'message'   => 'Success delete customer!',
                    ], 200);
                }
                
                return response()->json([
                    'success'   => false,
                    'message'   => 'Only accept CONFIRM DELETE!',
                ], 400);
            }
            
            return response()->json([
                'success'   => false,
                'message'   => 'Input is not valid!',
                'errors'    => $validator->errors()
            ], 400);
        }
        
        return response()->json([
            'success'   => false,
            'message'   => "Access Denied!"
        ], 400);
    }
}
