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
        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            DB::commit();
            return $this->Response(true, $user, 'User created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->Response(false, null, $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $this->ValidateRequest($request, [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'device_id' => 'required|string',
        ]);
        try {
            DB::beginTransaction();
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return $this->Response(false, null, 'User not found', 404);
            }
            if (!Hash::check($request->password, $user->password)) {
                return $this->Response(false, null, 'Invalid password', 401);
            }

            // Revoke all previous tokens (logout from all devices)
            $user->tokens()->delete();

            // Update device info
            $user->device_id = $request->device_id;
            $user->last_login_at = now();
            $user->save();

            $token = $user->createToken('auth_token')->accessToken;
            DB::commit();
            return $this->Response(true, ['token' => $token, 'user' => $user], 'User logged in successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->Response(false, null, $e->getMessage(), 500);
        }
    }
    public function refreshtoken(Request $request)
    {
        $this->ValidateRequest($request, [
            'device_id' => 'required|string',
        ]);
        
        try {
            $user = auth('api')->user();
            if (!$user) {
                return $this->NotAuthorized();
            }
            
            DB::beginTransaction();
            $user->token()->revoke();
            
            // Update device info
            $user->device_id = $request->device_id;
            $user->last_login_at = now();
            $user->save();
            
            $token = $user->createToken('auth_token')->accessToken;
            DB::commit();
            return $this->Response(true, ['token' => $token, 'user' => $user], 'Token refreshed successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->Response(false, null, $e->getMessage(), 500);
        }
    }
    public function logout(Request $request)
    {
        try {
            // dd('working');
            $user = auth('api')->user();
            if (!$user) {
                return $this->NotAuthorized();
            }
            DB::beginTransaction();
            $user->token()->revoke();
            DB::commit();
            return $this->Response(true, null, 'User logged out successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->Response(false, null, $e->getMessage(), 500);
        }
    }
    public function getusers(Request $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return $this->NotAuthorized();
            }
            $users = User::all();
            return $this->Response(true, $users, 'User data', 200);
        } catch (Exception $e) {
            return $this->Response(false, null, $e->getMessage(), 500);
        }
    }
    
}
