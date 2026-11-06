<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the six demo roles and accounts only.
 * Inventory is intentionally not created here: material boxes and stock
 * must enter through the supplier barcode scan at Incoming Warehouse.
 */
class StockTrackingSeeder extends Seeder
{
    public function run(): void
    {
        $roleNames = ['Staf Administrasi', 'Operator Warehouse', 'Dandori', 'Operator Assembly', 'Operator Delivery', 'Atasan'];
        $roles = [];
        foreach ($roleNames as $name) {
            $roles[$name] = Role::firstOrCreate(['role_name' => $name]);
        }

        $accounts = [
            ['admin', 'Admin Prototype', 'Staf Administrasi'],
            ['warehouse', 'Operator Warehouse Demo', 'Operator Warehouse'],
            ['dandori', 'Operator Dandori Demo', 'Dandori'],
            ['assembly', 'Operator Assembly Demo', 'Operator Assembly'],
            ['delivery', 'Operator Delivery Demo', 'Operator Delivery'],
            ['atasan', 'Atasan Demo', 'Atasan'],
        ];

        foreach ($accounts as [$username, $fullName, $roleName]) {
            User::firstOrCreate(
                ['username' => $username],
                [
                    'role_id' => $roles[$roleName]->role_id,
                    'password_hash' => Hash::make('Capstone123!'),
                    'full_name' => $fullName,
                    'status_account' => 'active',
                    'last_login' => null,
                ]
            );
        }

        $this->command?->info('Akun demo berhasil disiapkan. Password demo: Capstone123!. Tidak ada box/stok awal yang dibuat otomatis; masukkan material melalui Incoming Warehouse agar mengikuti alur TO-BE.');
    }
}
