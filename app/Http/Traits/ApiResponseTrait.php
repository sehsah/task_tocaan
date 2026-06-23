<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    /**
     * Return a successful response with data.
     *
     * @param  JsonResource|ResourceCollection|array<string, mixed>|null  $data
     */
    protected function successResponse(
        JsonResource|ResourceCollection|array|null $data = null,
        string $message = 'Success.',
        int $statusCode = 200,
    ): JsonResponse {
        $payload = ['success' => true, 'message' => $message];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Return a 201 Created response.
     *
     * @param  JsonResource|ResourceCollection|array<string, mixed>|null  $data
     */
    protected function createdResponse(
        JsonResource|ResourceCollection|array|null $data = null,
        string $message = 'Resource created successfully.',
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a 200 response with no data payload (e.g. for deletes/logouts).
     */
    protected function messageResponse(string $message, int $statusCode = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message], $statusCode);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $statusCode);
    }

    /**
     * Return a 401 Unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized.'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a 403 Forbidden response.
     */
    protected function forbiddenResponse(string $message = 'Forbidden.'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return a 404 Not Found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return a 422 Unprocessable Entity response.
     */
    protected function unprocessableResponse(string $message): JsonResponse
    {
        return $this->errorResponse($message, 422);
    }

    /**
     * Return a 500 Server Error response.
     */
    protected function serverErrorResponse(string $message = 'An unexpected error occurred.'): JsonResponse
    {
        return $this->errorResponse($message, 500);
    }
}
