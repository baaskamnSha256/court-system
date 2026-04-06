<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class DefendantRegistrySearchService
{
    /**
     * Регистрийн дугаараар иргэний мэдээлэл авах (ж: Хурлын зар товлох, XYP гэх мэт гадаад REST API).
     *
     * @return array{results: array<int, array{name: string, registry: string}>, message: ?string, mock?: bool}
     */
    public function search(string $registry): array
    {
        $registry = mb_strtoupper(trim($registry), 'UTF-8');
        $url = $this->resolvedApiUrl();

        if ($url === '') {
            return [
                'results' => [
                    ['name' => 'Туршилтын хүн ('.$registry.')', 'registry' => $registry],
                ],
                'message' => 'API тохируулаагүй тул туршилтын өгөгдөл.',
                'mock' => true,
            ];
        }

        $config = config('services.defendant_search', []);
        $protocol = strtolower((string) ($config['protocol'] ?? 'rest'));
        $method = strtoupper((string) ($config['method'] ?? 'GET'));
        $timeout = (int) ($config['timeout'] ?? 15);

        $client = Http::timeout($timeout);
        $curlOptions = [];

        $certPath = trim((string) ($config['cert_path'] ?? ''));
        if ($certPath !== '') {
            $certType = strtoupper((string) ($config['cert_type'] ?? 'P12'));
            $certPassword = (string) ($config['cert_passphrase'] ?? '');
            if ($certType === 'P12') {
                if (defined('CURLOPT_SSLCERT')) {
                    $curlOptions[CURLOPT_SSLCERT] = $certPath;
                }
                if (defined('CURLOPT_SSLCERTTYPE')) {
                    $curlOptions[CURLOPT_SSLCERTTYPE] = 'P12';
                }
                if ($certPassword !== '') {
                    if (defined('CURLOPT_SSLCERTPASSWD')) {
                        $curlOptions[CURLOPT_SSLCERTPASSWD] = $certPassword;
                    } elseif (defined('CURLOPT_KEYPASSWD')) {
                        $curlOptions[CURLOPT_KEYPASSWD] = $certPassword;
                    }
                }
            } else {
                $client = $client->withOptions([
                    'cert' => $certPassword !== '' ? [$certPath, $certPassword] : $certPath,
                ]);
            }
        }

        if ((bool) ($config['ssl_no_revoke'] ?? false)) {
            if (defined('CURLOPT_SSL_OPTIONS') && defined('CURLSSLOPT_NO_REVOKE')) {
                $curlOptions[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NO_REVOKE;
            }
        }

        if ($curlOptions !== []) {
            $client = $client->withOptions([
                'curl' => $curlOptions,
            ]);
        }

        $verify = (bool) ($config['verify'] ?? true);
        if (! $verify) {
            $client = $client->withoutVerifying();
        } else {
            $caBundle = trim((string) ($config['ca_bundle'] ?? ''));
            if ($caBundle !== '') {
                $client = $client->withOptions([
                    'verify' => $caBundle,
                ]);
            }
        }

        $token = trim((string) ($config['token'] ?? ''));
        $tokenHeader = trim((string) ($config['token_header'] ?? 'Authorization'));
        if ($token !== '' && $tokenHeader !== '') {
            if (strtolower($tokenHeader) === 'authorization') {
                $client = $client->withToken($token);
            } else {
                $client = $client->withHeaders([$tokenHeader => $token]);
            }
        }

        $extraHeaders = $config['headers'] ?? [];
        if (is_array($extraHeaders) && $extraHeaders !== []) {
            $client = $client->withHeaders($extraHeaders);
        }

        try {
            if ($protocol === 'soap') {
                return $this->searchSoap($client, $url, $config, $registry);
            }

            $client = $client->acceptJson();

            if ($method === 'POST') {
                $template = (string) ($config['post_body_template'] ?? '{"registry":"{registry}"}');
                $bodyString = str_replace('{registry}', $registry, $template);
                $body = json_decode($bodyString, true);
                if (! is_array($body)) {
                    return [
                        'results' => [],
                        'message' => 'POST биеийн JSON буруу (DEFENDANT_SEARCH_API_POST_BODY).',
                    ];
                }
                $response = $client->post($url, $body);
            } else {
                $param = (string) ($config['query_param'] ?? 'registry');
                $response = $client->get($url, [$param => $registry]);
            }

            if (! $response->successful()) {
                return [
                    'results' => [],
                    'message' => 'Гадаад системийн алдаа.',
                ];
            }

            $data = $response->json();
            $results = $this->normalizeApiResults($data, $registry);

            return [
                'results' => $results,
                'message' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'results' => [],
                'message' => 'Хайлт амжилтгүй: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{results: array<int, array{name: string, registry: string}>, message: ?string}
     */
    private function searchSoap($client, string $url, array $config, string $registry): array
    {
        $method = trim((string) ($config['soap_method'] ?? 'WS100101_getCitizenIDCardInfo'));
        $soapAction = (string) ($config['soap_action'] ?? '');
        $requestTemplate = (string) ($config['soap_request_template'] ?? '<request><regnum>{registry}</regnum></request>');
        $requestBodyXml = str_replace('{registry}', $registry, $requestTemplate);
        $envelope = $this->buildSoapEnvelope($method, $requestBodyXml);
        $timestampHeader = trim((string) ($config['timestamp_header'] ?? 'timeStamp'));
        $timestampValue = trim((string) ($config['timestamp_value'] ?? ''));
        if ($timestampValue === '') {
            $timestampValue = (string) round(microtime(true) * 1000);
        }
        $signatureHeader = trim((string) ($config['signature_header'] ?? 'signature'));
        $signatureValue = trim((string) ($config['signature_value'] ?? ''));
        if ($signatureValue === '') {
            $token = trim((string) ($config['token'] ?? ''));
            $signatureValue = $this->buildXypSignature($config, $token, $timestampValue);
        }

        $headers = [
            'Content-Type' => 'text/xml; charset=UTF-8',
            'SOAPAction' => $soapAction,
        ];
        if ($timestampHeader !== '') {
            $headers[$timestampHeader] = $timestampValue;
        }
        if ($signatureHeader !== '' && $signatureValue !== '') {
            $headers[$signatureHeader] = $signatureValue;
        }

        $response = $client
            ->withHeaders($headers)
            ->send('POST', $url, ['body' => $envelope]);

        if (! $response->successful()) {
            return [
                'results' => [],
                'message' => 'Гадаад системийн алдаа.',
            ];
        }

        return $this->normalizeSoapResponse($response->body(), $registry);
    }

    /**
     * key.key ашиглан accessToken.timeStamp өгөгдөлд гарын үсэг зурж base64 signature үүсгэнэ.
     */
    private function buildXypSignature(array $config, string $accessToken, string $timestamp): string
    {
        if ($accessToken === '' || $timestamp === '') {
            return '';
        }

        $keyPath = trim((string) ($config['xyp_key_path'] ?? ''));
        if ($keyPath === '') {
            return '';
        }

        $resolvedPath = str_starts_with($keyPath, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $keyPath) === 1
            ? $keyPath
            : base_path($keyPath);

        if (! is_file($resolvedPath)) {
            return '';
        }

        $privateKey = @file_get_contents($resolvedPath);
        if (! is_string($privateKey) || $privateKey === '') {
            return '';
        }

        $payload = $accessToken.'.'.$timestamp;
        $signature = '';
        $signed = @openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $signed ? base64_encode($signature) : '';
    }

    private function buildSoapEnvelope(string $method, string $requestBodyXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://citizen.xyp.gov.mn/">'
            .'<soapenv:Header/>'
            .'<soapenv:Body>'
            .'<tns:'.$method.'>'
            .$requestBodyXml
            .'</tns:'.$method.'>'
            .'</soapenv:Body>'
            .'</soapenv:Envelope>';
    }

    /**
     * @return array{results: array<int, array{name: string, registry: string}>, message: ?string}
     */
    private function normalizeSoapResponse(string $xml, string $registry): array
    {
        try {
            $root = new SimpleXMLElement($xml);
            $root->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $bodyList = $root->xpath('//soap:Body');
            $body = is_array($bodyList) ? ($bodyList[0] ?? null) : null;

            if (! $body instanceof SimpleXMLElement) {
                return ['results' => [], 'message' => 'SOAP хариу уншихад алдаа гарлаа.'];
            }

            $faultList = $body->xpath('./*[local-name()="Fault"]');
            $fault = is_array($faultList) ? ($faultList[0] ?? null) : null;
            if ($fault instanceof SimpleXMLElement) {
                $faultString = $fault->xpath('./*[local-name()="faultstring"]/text()');
                $faultMessage = trim((string) (($faultString[0] ?? null) ?: 'SOAP алдаа.'));

                return ['results' => [], 'message' => $faultMessage];
            }

            $responseList = $body->xpath('./*[contains(local-name(),"Response")]');
            $responseNode = is_array($responseList) ? ($responseList[0] ?? null) : null;

            if (! $responseNode instanceof SimpleXMLElement) {
                return ['results' => [], 'message' => 'SOAP response бүтэц танигдсангүй.'];
            }

            $returnList = $responseNode->xpath('./*[local-name()="return"]');
            $returnNode = is_array($returnList) ? ($returnList[0] ?? null) : null;
            if (! $returnNode instanceof SimpleXMLElement) {
                return ['results' => [], 'message' => 'SOAP return бүтэц танигдсангүй.'];
            }

            $resultCode = trim((string) ($returnNode->resultCode ?? ''));
            $resultMessage = trim((string) ($returnNode->resultMessage ?? ''));

            if ($resultCode !== '' && $resultCode !== '0') {
                return [
                    'results' => [],
                    'message' => $resultMessage !== '' ? $resultMessage : 'Гадаад системийн алдаа.',
                ];
            }

            $payloadNode = null;
            $responsePayloadList = $returnNode->xpath('./*[local-name()="response"]');
            $responsePayloadNode = is_array($responsePayloadList) ? ($responsePayloadList[0] ?? null) : null;
            if ($responsePayloadNode instanceof SimpleXMLElement) {
                $payloadNode = $responsePayloadNode;
            }

            if (! $payloadNode instanceof SimpleXMLElement) {
                $requestList = $returnNode->xpath('./*[local-name()="request"]');
                $requestNode = is_array($requestList) ? ($requestList[0] ?? null) : null;
                if ($requestNode instanceof SimpleXMLElement) {
                    $payloadNode = $requestNode;
                }
            }

            if (! $payloadNode instanceof SimpleXMLElement) {
                return ['results' => [], 'message' => $resultMessage !== '' ? $resultMessage : 'Мэдээлэл олдсонгүй.'];
            }

            $data = [];
            foreach ($payloadNode->xpath('./*') ?: [] as $child) {
                $data[$child->getName()] = trim((string) $child);
            }

            $results = $this->normalizeApiResults($data, $registry);

            return [
                'results' => $results,
                'message' => $resultMessage !== '' ? $resultMessage : null,
            ];
        } catch (\Throwable $e) {
            return [
                'results' => [],
                'message' => 'SOAP хариу уншихад алдаа гарлаа: '.$e->getMessage(),
            ];
        }
    }

    private function resolvedApiUrl(): string
    {
        $modern = (string) (config('services.defendant_search.url') ?? '');

        return $modern !== '' ? $modern : (string) config('services.defendant_search_api_url', '');
    }

    /**
     * @param  mixed  $data
     * @return array<int, array{name: string, registry: string}>
     */
    private function normalizeApiResults($data, string $registry): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['response']) && is_array($data['response'])) {
            $data = $data['response'];
        }

        if (isset($data['citizen']) && is_array($data['citizen'])) {
            $row = $this->normalizeRow($data['citizen'], $registry);

            return $row !== null ? [$row] : [];
        }

        $rows = $this->extractResultRows($data);
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $normalized = $this->normalizeRow($row, $registry);
            if ($normalized !== null) {
                $out[] = $normalized;
            }
        }

        return $out;
    }

    /**
     * @return array<int, mixed>
     */
    private function extractResultRows(array $data): array
    {
        foreach (['results', 'data', 'items', 'citizens', 'content', 'list'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        if ($this->resolveFullName($data) !== '' || $this->hasRegistryLikeKeys($data)) {
            return [$data];
        }

        return [];
    }

    private function hasRegistryLikeKeys(array $row): bool
    {
        foreach (['registry', 'regno', 'regnum', 'registerNo', 'register_number', 'civilId', 'civilID', 'register'] as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && (string) $row[$k] !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{name: string, registry: string}|null
     */
    private function normalizeRow(array $row, string $fallbackRegistry): ?array
    {
        $name = $this->resolveFullName($row);
        $reg = $row['registry'] ?? $row['regno'] ?? $row['regnum'] ?? $row['registerNo'] ?? $row['register_number'] ?? $row['civilId'] ?? $row['civilID'] ?? $row['register'] ?? $fallbackRegistry;
        $regStr = is_string($reg) ? mb_strtoupper(trim($reg), 'UTF-8') : $fallbackRegistry;

        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'registry' => $regStr,
        ];
    }

    private function resolveFullName(array $row): string
    {
        $direct = $row['name'] ?? $row['fullName'] ?? $row['full_name'] ?? $row['citizenName'] ?? $row['nerBuren'] ?? $row['full_name_mn'] ?? '';
        if (is_string($direct) && trim($direct) !== '') {
            return trim($direct);
        }

        $ovog = trim((string) ($row['ovog'] ?? $row['lastName'] ?? $row['lastname'] ?? $row['surname'] ?? ''));
        $ner = trim((string) ($row['ner'] ?? $row['firstName'] ?? $row['firstname'] ?? $row['givenName'] ?? ''));
        $firstname = trim((string) ($row['firstname'] ?? $row['firstName'] ?? ''));
        $lastname = trim((string) ($row['lastname'] ?? $row['lastName'] ?? ''));

        if ($firstname !== '' || $lastname !== '') {
            return trim($lastname.' '.$firstname);
        }

        if ($ovog !== '' && $ner !== '') {
            return $ovog.' овогтой '.$ner;
        }

        if ($ner !== '') {
            return $ner;
        }

        return $ovog;
    }
}
