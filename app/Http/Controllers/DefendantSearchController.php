<?php

namespace App\Http\Controllers;

use App\Services\DefendantRegistrySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DefendantSearchController extends Controller
{
    /**
     * Регистрийн дугаараар хайлт — гадаад API (тохиргооноос) эсвэл mock.
     */
    public function search(Request $request, DefendantRegistrySearchService $registrySearch): JsonResponse
    {
        $request->validate([
            'registry' => ['required', 'string', 'max:50'],
        ]);

        $registry = trim((string) $request->input('registry'));
        $out = $registrySearch->search($registry);

        $payload = ['results' => $out['results']];
        if (! empty($out['message'])) {
            $payload['message'] = $out['message'];
        }

        return response()->json($payload);
    }
}
