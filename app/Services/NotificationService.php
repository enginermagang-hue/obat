<?php

namespace App\Services;

use App\Models\PermintaanObat;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class NotificationService
{
    public function notifyRole(
        string $role,
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null,
        ?string $color = null,
    ): void {
        $roleModel = Role::findByName($role);

        $this->eachUser($roleModel->users->all(), $title, $body, $url, $icon, $color);
    }

    public function notifyFaskesUsers(
        ?int $faskesId,
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null,
        ?string $color = null,
    ): void {
        if (blank($faskesId)) {
            return;
        }

        $this->eachUser(
            User::where('fasilitas_kesehatan_id', $faskesId)->get()->all(),
            $title,
            $body,
            $url,
            $icon,
            $color,
        );
    }

    public function notifyPermintaanApprovers(
        PermintaanObat $permintaan,
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null,
        ?string $color = null,
    ): void {
        if ($permintaan->tipe_permintaan === 'pustu_ke_puskesmas') {
            $this->notifyFaskesUsers($permintaan->fasilitas_tujuan_id, $title, $body, $url, $icon, $color);

            return;
        }

        $this->notifyRole('admin_dinas', $title, $body, $url, $icon, $color);
        $this->notifyRole('admin_gudang', $title, $body, $url, $icon, $color);
    }

    private function eachUser(
        array $users,
        string $title,
        string $body,
        ?string $url,
        ?string $icon,
        ?string $color,
    ): void {
        DB::afterCommit(function () use ($users, $title, $body, $url, $icon, $color): void {
            foreach ($users as $user) {
                $notification = Notification::make()
                    ->title($title)
                    ->body($body);

                if ($url !== null) {
                    $notification->actions([
                        Action::make('view')
                            ->label('Lihat Detail')
                            ->url($url)
                            ->markAsRead(),
                    ]);
                }

                if ($icon !== null) {
                    $notification->icon($icon);
                }

                if ($color !== null) {
                    $notification->color($color);
                }

                $notification->sendToDatabase($user);
            }
        });
    }
}
