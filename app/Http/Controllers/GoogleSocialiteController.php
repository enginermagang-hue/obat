<?php

namespace App\Http\Controllers;

use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleSocialiteController extends Controller
{
    protected function linkingCallbackUrl(): string
    {
        return url('auth/google/callback/link');
    }

    public function redirectForLinking()
    {
        Log::info('[GoogleLink] redirectForLinking', [
            'user_id' => Auth::id(),
            'redirect_url' => $this->linkingCallbackUrl(),
        ]);

        return Socialite::driver('google')
            ->redirectUrl($this->linkingCallbackUrl())
            ->redirect();
    }

    public function handleLinkingCallback(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            Log::warning('[GoogleLink] callback - no user');

            return redirect()->route('login')
                ->with('error', __('Sesi Anda telah berakhir. Silakan login terlebih dahulu.'));
        }

        try {
            $oauthUser = Socialite::driver('google')
                ->redirectUrl($this->linkingCallbackUrl())
                ->user();

            Log::info('[GoogleLink] user from Google', ['id' => $oauthUser->getId(), 'email' => $oauthUser->getEmail()]);
        } catch (\Exception $e) {
            Log::error('[GoogleLink] user() failed', ['error' => $e->getMessage(), 'class' => get_class($e), 'code' => $e->getCode()]);

            return redirect()->route('filament.admin.pages.profil-saya')
                ->with('error', __('Gagal mendapatkan data dari Google. Silakan coba lagi.'));
        }

        $existing = SocialiteUser::query()
            ->where('provider', 'google')
            ->where('provider_id', $oauthUser->getId())
            ->first();

        if ($existing && $existing->user_id !== $user->id) {
            Log::warning('[GoogleLink] Google account already linked to another user', ['existing_user' => $existing->user_id]);

            return redirect()->route('filament.admin.pages.profil-saya')
                ->with('error', __('Akun Google ini sudah terhubung dengan pengguna lain.'));
        }

        SocialiteUser::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => 'google',
            ],
            [
                'provider_id' => $oauthUser->getId(),
            ]
        );

        Log::info('[GoogleLink] successfully linked');

        return redirect()->route('filament.admin.pages.profil-saya')
            ->with('success', __('Akun Google berhasil dihubungkan.'));
    }

    public function unlink()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        SocialiteUser::query()
            ->where('user_id', $user->id)
            ->where('provider', 'google')
            ->delete();

        return redirect()->route('filament.admin.pages.profil-saya')
            ->with('success', __('Akun Google berhasil diputuskan.'));
    }
}
