<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EnterpriseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Enterprise::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('sector')) {
            $query->where('sector', $request->sector);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $enterprises = $query->withCount(['branches', 'tickets'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($enterprises);
    }

    public function show(Enterprise $enterprise): JsonResponse
    {
        $enterprise->load(['branches', 'users' => fn ($q) => $q->where('role', '!=', 'super_admin')]);
        $enterprise->loadCount(['branches', 'tickets', 'queues']);
        return response()->json($enterprise);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'sector'           => 'required|in:banque,hopital,administration,autre',
            'address'          => 'nullable|string',
            'city'             => 'nullable|string',
            'contact_email'    => 'nullable|email',
            'contact_phone'    => 'nullable|string',
            'default_language' => 'nullable|in:fr,en',
        ]);

        $enterprise = Enterprise::create($data);

        return response()->json($enterprise, 201);
    }

    public function update(Request $request, Enterprise $enterprise): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'sector'           => 'sometimes|in:banque,hopital,administration,autre',
            'address'          => 'nullable|string',
            'city'             => 'nullable|string',
            'contact_email'    => 'nullable|email',
            'contact_phone'    => 'nullable|string',
            'default_language' => 'nullable|in:fr,en',
            'status'           => 'sometimes|in:pending,active,suspended',
        ]);

        $enterprise->update($data);

        return response()->json($enterprise);
    }

    public function approve(Enterprise $enterprise): JsonResponse
    {
        $enterprise->update(['status' => 'active']);
        return response()->json(['message' => 'Entreprise approuvée.', 'enterprise' => $enterprise]);
    }

    public function suspend(Enterprise $enterprise): JsonResponse
    {
        $enterprise->update(['status' => 'suspended']);
        return response()->json(['message' => 'Entreprise suspendue.', 'enterprise' => $enterprise]);
    }

    public function uploadLogo(Request $request, Enterprise $enterprise): JsonResponse
    {
        $request->validate(['logo' => 'required|image|max:2048']);

        if ($enterprise->logo) {
            Storage::disk('public')->delete($enterprise->logo);
        }

        $path = $request->file('logo')->store("enterprises/{$enterprise->id}/logo", 'public');
        $enterprise->update(['logo' => $path]);

        return response()->json(['logo' => Storage::url($path)]);
    }

    public function globalStats(): JsonResponse
    {
        return response()->json([
            'total_enterprises' => Enterprise::count(),
            'active'            => Enterprise::where('status', 'active')->count(),
            'pending'           => Enterprise::where('status', 'pending')->count(),
            'suspended'         => Enterprise::where('status', 'suspended')->count(),
            'total_users'       => User::whereIn('role', ['enterprise_admin', 'employee'])->count(),
        ]);
    }
}
