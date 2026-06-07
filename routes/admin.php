<?php

use App\Http\Controllers\Admin\ImpersonateRoleController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:Super Admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings/{key}', [SettingController::class, 'update'])->name('settings.update');

    Route::post('impersonate/{role}', [ImpersonateRoleController::class, 'store'])->name('impersonate.store');
});

// Stop impersonation — accessible while impersonating (outside role:Super Admin guard)
Route::delete('admin/impersonate', [ImpersonateRoleController::class, 'destroy'])->name('admin.impersonate.destroy');
