<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Config;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * successResponse
     *
     * @param  mixed  $message
     * @param  mixed  $result  - encourage to use Object instead Array
     * @param  mixed  $code
     * @return mixed response
     */
    public static function successResponse(string $message, $result = null, $code = Response::HTTP_OK)
    {
        $config = Config::where('name', Config::NAME_OUTLET_NAME)->first();

        $outletData = [
            'outlet_id' => $config->outlet_id,
            'name' => $config->outlet_name,
        ];

        return response()->json([
            'message' => $message,
            'data' => $result,
            'outlet' => $outletData,
        ], $code);
    }

    public static function customValidationException(array $errors)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation Error',
            'errors' => $errors,
        ], 422));
    }


    // CALVIN CHONG
    // CALVIN CHONG2
}

// eeerr333