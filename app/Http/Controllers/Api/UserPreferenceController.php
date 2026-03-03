<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller {
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
            $rules['palette'] = 'required|string';
        }

        if ($isAppearance) {
            $rules['appearance'] = 'required|string';
        }

        if ($isDateTime) {
            $rules['timezone'] = 'required|string';
            $rules['week_start'] = 'required|string';
            $rules['time_format'] = 'required|string';
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
