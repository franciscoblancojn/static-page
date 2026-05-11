<?php

class STPA_API
{
    static protected $URL_ENDPOINT = "/";
    static protected $METHODS = 'POST';

    public static function getApiKey()
    {
        $key = get_option(STPA_KEY . "_API_KEY");
        if (!$key) {
            $key = wp_generate_password(64, false, false);
            update_option(STPA_KEY . "_API_KEY", $key);
        }

        return $key;
    }
    public static function init()
    {
        register_rest_route(STPA_KEY, static::$URL_ENDPOINT, [
            'methods' => static::$METHODS,
            'callback' => function ($request) {
                try {
                    static::validateApiKey($request);
                    static::validateEnpoint($request);
                    return static::enpoint($request);
                } catch (\Throwable $th) {
                    return [
                        'success' => false,
                        'message' => $th->getMessage(),
                    ];
                }
            },
            'permission_callback' => function () {
                return self::permission_callback();
            }
        ]);
    }
    public static function validateApiKey($request)
    {
        $apiKey = $request['api-key'];
        if ($apiKey != self::GetApiKey()) {
            throw new Exception("Api key Invalid");
        }
    }
    public static function validateEnpoint($request) {}
    public static function permission_callback()
    {
        return true;
    }
    public static function enpoint($request)
    {
        return [
            'success' => true,
            'message' => "Message",
        ];
    }
}
