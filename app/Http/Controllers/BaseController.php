<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    public function ValidateRequest($request, $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            abort(response()->json([        //Here we use abort to stop the execution of the request and return the response
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422));
        }
    }
    public function Response($status, $data, $message, $code)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
    public function NotAuthorized(){
        return $this->Response(false, null, 'Not authorized', 401);
    }
}
