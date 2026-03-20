<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DefendantSearchController extends Controller
{
    /**
     * Регистрийн дугаараар хайлт — гадаад API дуудаж нэр буцаана.
     * API байхгүй бол mock жагсаалт буцаана (тохиргоо, тест).
     */
    public function search(Request $request)
    {
        $request->validate([
            'registry' => ['required', 'string', 'max:50'],
        ]);

        $registry = trim($request->input('registry'));
        $apiUrl = config('services.defendant_search_api_url');

        if (empty($apiUrl)) {
            return $this->mockSearchResult($registry);
        }

        try {
            $url = rtrim($apiUrl, '?&') . (str_contains($apiUrl, '?') ? '&' : '?') . 'registry=' . urlencode($registry);
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'results' => [],
                    'message' => 'Гадаад системийн алдаа.',
                ], 200);
            }

            $data = $response->json();
            $results = $this->normalizeApiResults($data, $registry);
            return response()->json(['results' => $results]);
        } catch (\Throwable $e) {
            return response()->json([
                'results' => [],
                'message' => 'Хайлт амжилтгүй: ' . $e->getMessage(),
            ], 200);
        }
    }

    private function normalizeApiResults($data, string $registry): array
    {
        if (isset($data['results']) && is_array($data['results'])) {
            return array_map(function ($row) use ($registry) {
                return [
                    'name' => $row['name'] ?? $row['ner'] ?? '',
                    'registry' => $row['registry'] ?? $row['regno'] ?? $registry,
                ];
            }, $data['results']);
        }
        if (isset($data['data']) && is_array($data['data'])) {
            return array_map(function ($row) use ($registry) {
                return [
                    'name' => $row['name'] ?? $row['ner'] ?? '',
                    'registry' => $row['registry'] ?? $row['regno'] ?? $registry,
                ];
            }, $data['data']);
        }
        if (isset($data['name'])) {
            return [['name' => $data['name'], 'registry' => $data['registry'] ?? $registry]];
        }
        return [];
    }

    private function mockSearchResult(string $registry): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'results' => [
                ['name' => 'Туршилтын хүн (' . $registry . ')', 'registry' => $registry],
            ],
            'message' => 'API тохируулаагүй тул туршилтын өгөгдөл.',
        ]);
    }
}
