<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QrCode;
use App\Models\User;

class QrCodePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, QrCode $qrCode): bool
    {
        return $this->isAdmin($user) || $user->id === $qrCode->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, QrCode $qrCode): bool
    {
        return $this->isAdmin($user) || $user->id === $qrCode->user_id;
    }

    public function delete(User $user, QrCode $qrCode): bool
    {
        return $this->isAdmin($user) || $user->id === $qrCode->user_id;
    }

    public function restore(User $user, QrCode $qrCode): bool
    {
        return $this->isAdmin($user) || $user->id === $qrCode->user_id;
    }

    public function forceDelete(User $user, QrCode $qrCode): bool
    {
        return $this->isAdmin($user) || $user->id === $qrCode->user_id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
