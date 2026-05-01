<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserPreferenceController extends Controller {

    /**
     * Allowed palette names. Must stay in sync with the keys of
     * tazko-frontend/src/resources/palettes.js — the frontend reads the
     * persisted value back to apply the corresponding CSS variables.
     */
    private const ALLOWED_PALETTES = [
        'aurora', 'ocean', 'sunset', 'forest', 'rose', 'mono',
    ];

    public function show() {
        $user = auth()->user();
        return response()->json([
            'preference' => $user->preference
        ]);
    }

    public function store(Request $request) {
        $isPalette = $request->has('palette');
        $isAppearance = $request->has('appearance');
        $isDateTime = $request->has('timezone') || $request->has('week_start') || $request->has('time_format');

        $rules = [];

        if ($isPalette) {
            $rules['palette'] = ['required', 'string', Rule::in(self::ALLOWED_PALETTES)];
        }

        if ($isAppearance) {
            $rules['appearance'] = ['required', Rule::in(['light', 'dark', 'os'])];
        }

        if ($isDateTime) {
            // 'timezone' is a built-in Laravel rule that validates against
            // PHP's known-timezone list — guards against typos like 'EST'
            // (not a valid PHP timezone) reaching the frontend formatter.
            $rules['timezone']    = ['required', 'timezone'];
            $rules['week_start']  = ['required', Rule::in(['monday', 'sunday'])];
            $rules['time_format'] = ['required', Rule::in(['12', '24'])];
        }

        $validated = $request->validate($rules);

        $request->user()->preference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        if ($isPalette && !$isAppearance && !$isDateTime) {
            $message = 'Palette updated successfully';
        } elseif ($isAppearance && !$isPalette && !$isDateTime) {
            $message = 'Appearance updated successfully';
        } elseif ($isDateTime && !$isPalette && !$isAppearance) {
            $message = 'Date/Time preferences updated successfully';
        } else {
            $message = 'Preferences updated successfully';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }
}
