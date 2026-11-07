
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StockTrackingSeeder extends Seeder
{
    public function run(): void
    {
        $demoPassword = (string) env('DEMO_USER_PASSWORD', 'Capstone123!');

        if (
            app()->environment('production') &&
            (!env('DEMO_USER_PASSWORD') || strlen($demoPassword) < 12)
        ) {
            throw new \RuntimeException(
                'Set DEMO_USER_PASSWORD dengan minimal 12 karakter melalui Laravel Cloud Secrets.'
            );
        }

        $roleNames = [
            'Staf Administrasi',
            'Operator Warehouse',
            'Dandori',
            'Operator Assembly',
            'Operator Delivery',
            'Atasan',
        ];

        $roles = [];

        foreach ($roleNames as $name) {
            $roles[$name] = Role::firstOrCreate([
                'role_name' => $name,
            ]);
        }

        $accounts = [
            ['admin', 'Admin Prototype', 'Staf Administrasi'],
            ['warehouse', 'Operator Warehouse Demo', 'Operator Warehouse'],
            ['dandori', 'Operator Dandori Demo', 'Dandori'],
            ['assembly', 'Operator Assembly Demo', 'Operator Assembly'],
            ['delivery', 'Operator Delivery Demo', 'Operator Delivery'],
            ['atasan', 'Atasan Demo', 'Atasan'],
        ];

        // Buat satu hash untuk password baru.
        $passwordHash = Hash::make($demoPassword);

        // Buat akun apabila belum tersedia.
        foreach ($accounts as [$username, $fullName, $roleName]) {
            User::firstOrCreate(
                ['username' => $username],
                [
                    'role_id' => $roles[$roleName]->role_id,
                    'password_hash' => $passwordHash,
                    'full_name' => $fullName,
                    'status_account' => 'active',
                    'last_login' => null,
                ]
            );
        }

        // Perbarui password keenam akun yang sudah ada.
        // Tidak mengubah role, nama, status, maupun data lainnya.
        User::whereIn('username', array_column($accounts, 0))
            ->update([
                'password_hash' => $passwordHash,
            ]);

        $this->command?->info(
            'Password keenam akun demo berhasil diperbarui menggunakan DEMO_USER_PASSWORD.'
        );
    }
}
