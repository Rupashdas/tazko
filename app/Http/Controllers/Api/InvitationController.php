<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class InvitationController extends Controller implements HasMiddleware {

    public static function middleware(): array {
        return [
            new Middleware('capability:users.create',  only: ['store', 'resend', 'destroy']),
            new Middleware('capability:users.view',    only: ['index']),
        ];
    }

    /**
     * GET /api/invitations
     * List all non-accepted invitations (pending + expired).
     */
    public function index(): JsonResponse {
        $invitations = Invitation::with('role', 'invitedBy')
            ->whereNull('accepted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $invitations->map(fn($inv) => $this->formatInvitation($inv)),
        ]);
    }

    /**
     * POST /api/invitations
     * Send an invitation email.
     */
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email',
            'role_id' => 'nullable|integer|exists:roles,id',
        ]);

        // Cancel any previous pending invite for this email
        Invitation::where('email', $validated['email'])->delete();

        $invitation = Invitation::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role_id'    => $validated['role_id'] ?? null,
            'invited_by' => auth()->id(),
            'token'      => Invitation::generateToken(),
            'expires_at' => now()->addHours(72),
        ]);

        $invitation->load('role', 'invitedBy');

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return response()->json([
            'status'  => 'success',
            'message' => 'Invitation sent to ' . $invitation->email,
            'data'    => $this->formatInvitation($invitation),
        ], 201);
    }

    /**
     * GET /api/invitations/{token}  — PUBLIC
     * Validate token before showing the accept form.
     */
    public function show(string $token): JsonResponse {
        $invitation = Invitation::with('role', 'invitedBy')
            ->where('token', $token)
            ->first();

        if (!$invitation) {
            return response()->json(['status' => 'error', 'message' => 'Invitation not found.'], 404);
        }

        if ($invitation->isAccepted()) {
            return response()->json(['status' => 'error', 'message' => 'This invitation has already been accepted.'], 410);
        }

        if ($invitation->isExpired()) {
            return response()->json(['status' => 'error', 'message' => 'This invitation has expired.'], 410);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'name'       => $invitation->name,
                'email'      => $invitation->email,
                'role'       => $invitation->role?->label,
                'invited_by' => $invitation->invitedBy->name,
                'expires_at' => $invitation->expires_at,
            ],
        ]);
    }

    /**
     * POST /api/invitations/{token}/accept  — PUBLIC
     * User sets password and account is created.
     */
    public function accept(Request $request, string $token): JsonResponse {
        $invitation = Invitation::with('role')
            ->where('token', $token)
            ->first();

        if (!$invitation) {
            return response()->json(['status' => 'error', 'message' => 'Invitation not found.'], 404);
        }

        if ($invitation->isAccepted()) {
            return response()->json(['status' => 'error', 'message' => 'This invitation has already been accepted.'], 410);
        }

        if ($invitation->isExpired()) {
            return response()->json(['status' => 'error', 'message' => 'This invitation has expired. Please ask for a new one.'], 410);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'name'     => $invitation->name,
            'email'    => $invitation->email,
            'password' => Hash::make($request->password),
        ]);

        $user->preference()->create([]);

        if ($invitation->role_id) {
            $user->roles()->sync([$invitation->role_id]);
        }

        // Mark invitation as accepted
        $invitation->update(['accepted_at' => now()]);

        $user->load('roles.capabilities');

        return response()->json([
            'status'  => 'success',
            'message' => 'Account created successfully. You can now log in.',
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * POST /api/invitations/{invitation}/resend
     * Resend / refresh an expired or pending invitation.
     */
    public function resend(Invitation $invitation): JsonResponse {
        if ($invitation->isAccepted()) {
            return response()->json(['status' => 'error', 'message' => 'This invitation was already accepted.'], 422);
        }

        $invitation->update([
            'token'      => Invitation::generateToken(),
            'expires_at' => now()->addHours(72),
        ]);

        $invitation->load('role', 'invitedBy');

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return response()->json([
            'status'  => 'success',
            'message' => 'Invitation resent to ' . $invitation->email,
            'data'    => $this->formatInvitation($invitation),
        ]);
    }

    /**
     * DELETE /api/invitations/{invitation}
     * Cancel a pending invitation.
     */
    public function destroy(Invitation $invitation): JsonResponse {
        if ($invitation->isAccepted()) {
            return response()->json(['status' => 'error', 'message' => 'Cannot cancel an accepted invitation.'], 422);
        }

        $invitation->delete();

        return response()->json(['status' => 'success', 'message' => 'Invitation cancelled.']);
    }

    /*---------------------------------------------------------------------------
    | Private helpers
    ---------------------------------------------------------------------------*/

    private function formatInvitation(Invitation $inv): array {
        return [
            'id'         => $inv->id,
            'name'       => $inv->name,
            'email'      => $inv->email,
            'role'       => $inv->role ? ['id' => $inv->role->id, 'label' => $inv->role->label] : null,
            'invited_by' => [
                'name' => $inv->invitedBy->name,
                'avatar' => $inv->invitedBy->avatar ? asset('storage/' . $inv->invitedBy->avatar) : null,
            ],
            'status'     => $inv->status,
            'expires_at' => $inv->expires_at,
            'created_at' => $inv->created_at,
        ];
    }
}
