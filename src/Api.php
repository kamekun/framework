<?php

namespace Sokeio;

class Api
{
    public const OK = 200;

    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const PAYMENT_REQUIRED = 402;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const NOT_ACCEPTABLE = 406;
    public const PROXY_AUTHENTICATION_REQUIRED = 407;
    public const REQUEST_TIMEOUT = 408;
    public const CONFLICT = 409;
    public const GONE = 410;
    public const LENGTH_REQUIRED = 411;
    public const PRECONDITION_FAILED = 412;
    public const PAYLOAD_TOO_LARGE = 413;
    public const URI_TOO_LONG = 414;
    public const UNSUPPORTED_MEDIA_TYPE = 415;
    public const RANGE_NOT_SATISFIABLE = 416;
    public const EXPECTATION_FAILED = 417;

    public const  INTERNAL_SERVER_ERROR = 500;
    public const  NOT_IMPLEMENTED = 501;
    public const  BAD_GATEWAY = 502;
    public const  SERVICE_UNAVAILABLE = 503;
    public const  GATEWAY_TIMEOUT = 504;
    public const  HTTP_VERSION_NOT_SUPPORTED = 505;

    public static function Json($data = [], $message = [], $status = Api::OK, $meta = [])
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => $status,
            'meta' => $meta
        ]);
    }
}
