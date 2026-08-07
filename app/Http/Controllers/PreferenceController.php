<?php

namespace App\Http\Controllers;

use App\Onboarding\OnboardingProgress;
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
            OnboardingProgress::PREFERENCE_KEY => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $current = $user->preferences ?? [];

        if (isset($validated['columns'])) {
            $current['columns'] = array_merge(
                $current['columns'] ?? [],
                $validated['columns'],
            );
        }

        if (array_key_exists(OnboardingProgress::PREFERENCE_KEY, $validated)) {
            $current[OnboardingProgress::PREFERENCE_KEY] = $validated[OnboardingProgress::PREFERENCE_KEY];
        }

        $user->preferences = $current;
        $user->save();

        return response()->json($user->preferences);
    }
}
