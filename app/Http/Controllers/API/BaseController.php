<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseController extends Controller
{
    /**
     * Return a success response.
     *
     * @param  mixed  $data
     * @param  string|null  $message
     * @param  int  $status
     */
    public function handleResponse($data, $message = null, $status = Response::HTTP_OK): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $response['per_page'] = $data->perPage();
            $response['total'] = $data->total();
            $response['current_page'] = $data->currentPage();
            $response['last_page'] = $data->lastPage();
        }

        return response()->json($response, $status);
    }

    /**
     * Return an error response.
     *
     * @param  string|null  $message
     * @param  int  $status
     * @param  mixed  $errors
     */
    public function handleError($message = null, $status = Response::HTTP_BAD_REQUEST, $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
