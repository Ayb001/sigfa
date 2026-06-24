<?php

namespace App\Http\Controllers\EnterpriseAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EnterpriseProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $enterprise = auth('staff')->user()->enterprise;
        return response()->json($enterprise);
    }

    public function update(Request $request): JsonResponse
    {
        $enterprise = auth('staff')->user()->enterprise;

        $data = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'sector'           => 'sometimes|in:banque,hopital,administration,autre',
            'address'          => 'nullable|string',
            'city'             => 'nullable|string',
            'contact_email'    => 'nullable|email',
            'contact_phone'    => 'nullable|string',
            'default_language' => 'nullable|in:fr,en',
        ]);

        $enterprise->update($data);

        return response()->json($enterprise);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate(['logo' => 'required|image|max:2048']);

        $enterprise = auth('staff')->user()->enterprise;

        if ($enterprise->logo) {
            Storage::disk('public')->delete($enterprise->logo);
        }

        $path = $request->file('logo')->store("enterprises/{$enterprise->id}/logo", 'public');
        $enterprise->update(['logo' => $path]);

        return response()->json(['logo' => Storage::url($path)]);
    }
}
