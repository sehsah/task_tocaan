<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Register a new user and return a JWT token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $token = Auth::login($user);

        return $this->createdResponse(
            data: [
                'user' => new UserResource($user),
                'token' => $token,
                'type' => 'bearer',
            ],
            message: 'User registered successfully.',
        );
    }

    /**
     * Authenticate a user and return a JWT token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = Auth::attempt($credentials)) {
            return $this->unauthorizedResponse('Invalid credentials.');
        }

        return $this->successResponse(
            data: [
                'user' => new UserResource(Auth::user()),
                'token' => $token,
                'type' => 'bearer',
            ],
            message: 'Login successful.',
        );
    }

    /**
     * Invalidate the current JWT token (logout).
     */
    public function logout(): JsonResponse
    {
        Auth::logout();

        return $this->messageResponse('Logged out successfully.');
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(): JsonResponse
    {
        return $this->successResponse(
            data: ['user' => new UserResource(Auth::user())],
        );
    }
}
