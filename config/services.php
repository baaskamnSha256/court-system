<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Шүүгдэгч / иргэнийг регистрийн дугаараар хайх гадаад API (ж: хурлын зар товлох, XYP, дотоод REST).
    | DEFENDANT_SEARCH_API_URL хоосон бол mock өгөгдөл буцаана.
    |
    | GET: query_param-аар дамжуулна (анхны утга registry).
    | POST: DEFENDANT_SEARCH_API_POST_BODY нь JSON; {registry} placeholder-ийг бодит дугаараар солино.
    */
    'defendant_search_api_url' => env('DEFENDANT_SEARCH_API_URL', ''),

    'defendant_search' => [
        'url' => env('DEFENDANT_SEARCH_API_URL', ''),
        'protocol' => env('DEFENDANT_SEARCH_API_PROTOCOL', 'rest'),
        'method' => env('DEFENDANT_SEARCH_API_METHOD', 'GET'),
        'token' => env('DEFENDANT_SEARCH_API_TOKEN', ''),
        'token_header' => env('DEFENDANT_SEARCH_API_TOKEN_HEADER', 'Authorization'),
        'timeout' => (int) env('DEFENDANT_SEARCH_API_TIMEOUT', 15),
        'query_param' => env('DEFENDANT_SEARCH_API_QUERY_PARAM', 'registry'),
        'post_body_template' => env('DEFENDANT_SEARCH_API_POST_BODY', '{"registry":"{registry}"}'),
        'soap_action' => env('DEFENDANT_SEARCH_API_SOAP_ACTION', ''),
        'soap_method' => env('DEFENDANT_SEARCH_API_SOAP_METHOD', 'WS100101_getCitizenIDCardInfo'),
        'soap_request_template' => env('DEFENDANT_SEARCH_API_SOAP_REQUEST', '<request><regnum>{registry}</regnum></request>'),
        'timestamp_header' => env('DEFENDANT_SEARCH_API_TIMESTAMP_HEADER', 'timeStamp'),
        'timestamp_value' => env('DEFENDANT_SEARCH_API_TIMESTAMP_VALUE', ''),
        'signature_header' => env('DEFENDANT_SEARCH_API_SIGNATURE_HEADER', 'signature'),
        'signature_value' => env('DEFENDANT_SEARCH_API_SIGNATURE_VALUE', ''),
        'xyp_key_path' => env('DEFENDANT_SEARCH_API_XYP_KEY_PATH', ''),
        'cert_type' => env('DEFENDANT_SEARCH_API_CERT_TYPE', 'P12'),
        'cert_path' => env('DEFENDANT_SEARCH_API_CERT_PATH', ''),
        'cert_passphrase' => env('DEFENDANT_SEARCH_API_CERT_PASSPHRASE', ''),
        'ssl_no_revoke' => (bool) env('DEFENDANT_SEARCH_API_SSL_NO_REVOKE', false),
        'verify' => filter_var(env('DEFENDANT_SEARCH_API_VERIFY', 'true'), FILTER_VALIDATE_BOOL),
        'ca_bundle' => env('DEFENDANT_SEARCH_API_CA_BUNDLE', ''),
        'headers' => is_array($h = json_decode((string) env('DEFENDANT_SEARCH_API_HEADERS', ''), true)) ? $h : [],
    ],

    'notification' => [
        'enabled' => filter_var(env('NOTIFICATION_ENABLED', 'false'), FILTER_VALIDATE_BOOL),
        'access_token' => env('NOTIFICATION_ACCESS_TOKEN', ''),
        'token_url' => env('NOTIFICATION_TOKEN_URL', 'https://notification.mn/api/v1/external/token'),
        'notify_url' => env('NOTIFICATION_NOTIFY_URL', 'https://notification.mn/api/v1/notification'),
        'username' => env('NOTIFICATION_USERNAME', ''),
        'password' => env('NOTIFICATION_PASSWORD', ''),
        'timeout' => (int) env('NOTIFICATION_TIMEOUT', 15),
    ],

];
