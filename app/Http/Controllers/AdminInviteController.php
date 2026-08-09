<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminInviteController extends Controller
{
    // Lists outstanding + used teacher invites, newest first, and shows the
    // generate form. Replaces the old shared "LimenSpoon" key - every invite
    // here is single-use, optionally email-locked, and can carry an expiry.
    // (Admin invites no longer exist - there is one hardcoded admin account.)
    public function index(): View
    {
        $invites = Invite::with(['creator', 'usedByAdmin', 'usedByTeacher'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.invites', [
            'invites' => $invites,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'nullable|email|max:150',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        Invite::create([
            'code' => Invite::generateCode(),
            // Only teachers are ever invited through the app now - there is
            // exactly one, hardcoded admin account and no way to create more.
            'type' => 'teacher',
            'email' => $data['email'] ?? null,
            'created_by_admin_id' => Auth::guard('admin')->id(),
            'expires_at' => !empty($data['expires_in_days'])
                ? now()->addDays((int) $data['expires_in_days'])
                : null,
        ]);

        return redirect()->route('admin.invites.index')->with('status', 'Invite code generated.');
    }

    public function destroy(Invite $invite): RedirectResponse
    {
        // Only revoke codes that haven't been redeemed yet.
        if (!$invite->used_at) {
            $invite->delete();
        }

        return redirect()->route('admin.invites.index')->with('status', 'Invite revoked.');
    }
}
