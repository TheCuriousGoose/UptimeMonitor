<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->preferences ?? []);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'columns' => ['sometimes', 'array'],
            'columns.*' => ['array'],
            'columns.*.*' => ['boolean'],
        ]);

        $user = $request->user();
        $current = $user->preferences ?? [];

        if (isset($validated['columns'])) {
            $current['columns'] = array_merge(
                $current['columns'] ?? [],
                $validated['columns'],
            );
        }

        $user->preferences = $current;
        $user->save();

        return response()->json($user->preferences);
    }
}
