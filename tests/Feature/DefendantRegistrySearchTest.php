<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::query()->firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);
});

it('returns mock payload when api url is not configured', function () {
    Config::set('services.defendant_search_api_url', '');
    Config::set('services.defendant_search.url', '');
    Config::set('services.defendant_search.protocol', 'rest');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->getJson('/admin/defendant-search?registry=АБ12345678');

    $response->assertSuccessful();
    $response->assertJsonPath('results.0.registry', 'АБ12345678');
    $response->assertJsonPath('results.0.name', 'Туршилтын хүн (АБ12345678)');
    expect($response->json('message'))->not->toBeNull();
});

it('calls external get api and normalizes ovog ner shape', function () {
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        if (! str_starts_with($request->url(), 'https://registry.example/api')) {
            return Http::response('Not found', 404);
        }

        return Http::response([
            'data' => [
                [
                    'ovog' => 'Батаа',
                    'ner' => 'Дорж',
                    'registerNo' => 'АБ11111111',
                ],
            ],
        ], 200);
    });

    Config::set('services.defendant_search_api_url', 'https://registry.example/api');
    Config::set('services.defendant_search.url', 'https://registry.example/api');
    Config::set('services.defendant_search.protocol', 'rest');
    Config::set('services.defendant_search.method', 'GET');
    Config::set('services.defendant_search.token', '');
    Config::set('services.defendant_search.timeout', 10);
    Config::set('services.defendant_search.query_param', 'registry');
    Config::set('services.defendant_search.post_body_template', '{"registry":"{registry}"}');
    Config::set('services.defendant_search.headers', []);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->getJson('/admin/defendant-search?registry=аб11111111');

    $response->assertSuccessful();
    $response->assertJsonPath('results.0.name', 'Батаа овогтой Дорж');
    $response->assertJsonPath('results.0.registry', 'АБ11111111');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'https://registry.example/api')) {
            return false;
        }
        $query = parse_url($request->url(), PHP_URL_QUERY) ?? '';
        parse_str($query, $params);

        return ($params['registry'] ?? null) === 'АБ11111111';
    });
});

it('posts json body and sends bearer token when configured', function () {
    Http::fake([
        'https://registry.example/lookup' => Http::response([
            'citizen' => [
                'fullName' => 'Тест иргэн',
                'civilId' => 'УГ99999999',
            ],
        ], 200),
    ]);

    Config::set('services.defendant_search_api_url', 'https://registry.example/lookup');
    Config::set('services.defendant_search.url', 'https://registry.example/lookup');
    Config::set('services.defendant_search.method', 'POST');
    Config::set('services.defendant_search.protocol', 'rest');
    Config::set('services.defendant_search.token', 'secret-token');
    Config::set('services.defendant_search.token_header', 'Authorization');
    Config::set('services.defendant_search.timeout', 10);
    Config::set('services.defendant_search.query_param', 'registry');
    Config::set('services.defendant_search.post_body_template', '{"civilId":"{registry}","serviceCode":"WS100101_getCitizenIDCardInfo"}');
    Config::set('services.defendant_search.headers', []);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->getJson('/admin/defendant-search?registry=УГ99999999');

    $response->assertSuccessful();
    $response->assertJsonPath('results.0.name', 'Тест иргэн');
    $response->assertJsonPath('results.0.registry', 'УГ99999999');

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $request->url() === 'https://registry.example/lookup'
            && $request->hasHeader('Authorization', 'Bearer secret-token')
            && ($data['civilId'] ?? null) === 'УГ99999999';
    });
});

it('calls soap endpoint with accessToken header and parses response', function () {
    Http::fake([
        'https://xyp.gov.mn/citizen-1.3.0/ws' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ns2:WS100101_getCitizenIDCardInfoResponse xmlns:ns2="http://citizen.xyp.gov.mn/">
      <return>
        <request xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ns2:citizenRequestData">
          <firstname>Бат</firstname>
          <lastname>Дорж</lastname>
          <regnum>AB12345678</regnum>
        </request>
        <resultCode>0</resultCode>
        <resultMessage>success</resultMessage>
      </return>
    </ns2:WS100101_getCitizenIDCardInfoResponse>
  </soap:Body>
</soap:Envelope>
XML
            ,
            200,
            ['Content-Type' => 'text/xml; charset=UTF-8']
        ),
    ]);

    Config::set('services.defendant_search_api_url', 'https://xyp.gov.mn/citizen-1.3.0/ws');
    Config::set('services.defendant_search.url', 'https://xyp.gov.mn/citizen-1.3.0/ws');
    Config::set('services.defendant_search.protocol', 'soap');
    Config::set('services.defendant_search.method', 'POST');
    Config::set('services.defendant_search.token', 'xyp-token');
    Config::set('services.defendant_search.token_header', 'accessToken');
    Config::set('services.defendant_search.timeout', 10);
    Config::set('services.defendant_search.soap_method', 'WS100101_getCitizenIDCardInfo');
    Config::set('services.defendant_search.soap_action', '');
    Config::set('services.defendant_search.soap_request_template', '<request><regnum>{registry}</regnum></request>');
    Config::set('services.defendant_search.timestamp_header', 'timeStamp');
    Config::set('services.defendant_search.timestamp_value', '1775116708911');
    Config::set('services.defendant_search.signature_header', 'signature');
    Config::set('services.defendant_search.signature_value', 'signed-value');
    Config::set('services.defendant_search.headers', []);
    Config::set('services.defendant_search.cert_path', '');
    Config::set('services.defendant_search.cert_passphrase', '');
    Config::set('services.defendant_search.ssl_no_revoke', false);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->getJson('/admin/defendant-search?registry=AB12345678');

    $response->assertSuccessful();
    $response->assertJsonPath('results.0.name', 'Дорж Бат');
    $response->assertJsonPath('results.0.registry', 'AB12345678');

    Http::assertSent(function ($request) {
        $body = $request->body();

        return $request->url() === 'https://xyp.gov.mn/citizen-1.3.0/ws'
            && $request->method() === 'POST'
            && $request->hasHeader('accessToken', 'xyp-token')
            && $request->hasHeader('timeStamp', '1775116708911')
            && $request->hasHeader('signature', 'signed-value')
            && str_contains($body, '<tns:WS100101_getCitizenIDCardInfo>')
            && str_contains($body, '<regnum>AB12345678</regnum>');
    });
});
