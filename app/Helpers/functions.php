<?php

if (! function_exists('sendResponse')) {
    /**
     * Send a formatted JSON response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    function sendResponse($data = null, $message = 'Success', $code = 200)
    {
        $response = [
            'success' => $code >= 200 && $code < 300,
            'message' => $message,
            'data'    => $data,
        ];

        return response()->json($response, $code);
    }
}
