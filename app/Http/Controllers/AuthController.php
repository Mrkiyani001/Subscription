<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $this->ValidateRequest($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()->symbols()->uncompromised()],
            'password_confirmation' => 'required|min:8',
        ]);
        try{
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            DB::commit();
            return $this->Response(true, $user, 'User created successfully', 201);
        }catch(Exception $e){
            DB::rollBack();
            return $this->Response(false, null, $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $this->ValidateRequest($request, [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);
        try{
            DB::beginTransaction();
            $user = User::where('email', $request->email)->first();
            if(!$user){
                return $this->Response(false, null, 'User not found', 404);
            }
            if(!Hash::check($request->password, $user->password)){
                return $this->Response(false, null, 'Invalid password', 401);
            }
            $token = $user->createToken('auth_token')->accessToken;
            DB::commit();
            return $this->Response(true, ['token' => $token, 'user' => $user], 'User logged in successfully', 200);
        }catch(Exception $e){
            DB::rollBack();
            return $this->Response(false, null, $e->getMessage(), 500);
        }  
    }
}
