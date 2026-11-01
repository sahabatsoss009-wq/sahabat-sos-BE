<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\SOS;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel spesifik per-relawan (untuk penugasan SOS langsung ke 1 relawan)
Broadcast::channel('relawan.{id}', function (User $user, $id) {
    return $user->role === 'relawan' && (int) $user->id === (int) $id && $user->status_verifikasi === 'terverifikasi' && $user->is_active;
});

// Channel umum semua relawan (untuk broadcast SOS ke semua relawan)
Broadcast::channel('relawan-channel', function (User $user) {
    if (in_array($user->role, ['admin', 'superadmin'])) {
        return true;
    }
    return $user->role === 'relawan' && $user->status_verifikasi === 'terverifikasi' && $user->is_active;
});

// Channel Command Center Dashboard Admin
Broadcast::channel('command-center', function (User $user) {
    return in_array($user->role, ['admin', 'superadmin']);
});

// Channel Pemantauan Lokasi Real-time Seluruh Relawan
Broadcast::channel('relawan-locations', function (User $user) {
    return in_array($user->role, ['admin', 'superadmin']);
});

// Channel Broadcast Laporan Warga
Broadcast::channel('laporan-channel', function (User $user) {
    if (in_array($user->role, ['admin', 'superadmin'])) {
        return true;
    }
    return $user->role === 'relawan' && $user->status_verifikasi === 'terverifikasi' && $user->is_active;
});

// Channel Sirene Darurat Posko
Broadcast::channel('posko-channel', function (User $user) {
    if (in_array($user->role, ['admin', 'superadmin'])) {
        return true;
    }
    return $user->role === 'relawan' && $user->status_verifikasi === 'terverifikasi' && $user->is_active;
});

// Channel per-SOS (untuk tracking lokasi & update status)
Broadcast::channel('sos.{sosId}', function (User $user, $sosId) {
    $sos = SOS::find($sosId);
    if (!$sos) {
        return false;
    }

    if ((int) $user->id === (int) $sos->id_pengguna) {
        return true;
    }

    if (in_array($user->role, ['admin', 'superadmin'])) {
        return true;
    }

    return $user->role === 'relawan' && $user->status_verifikasi === 'terverifikasi' && $user->is_active;
});
