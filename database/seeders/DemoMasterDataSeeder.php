<?php

namespace Database\Seeders;

use App\Models\BillOfMaterial;
use App\Models\BoxPackagingQuantity;
use App\Models\Location;
use App\Models\Manpower;
use App\Models\Part;
use App\Models\ProductionPlan;
use App\Models\Supplier;
use App\Models\UnitOfMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Adds anonymized, fictional master data for the Stock Tracking Capstone demo.
 * Idempotent: rerunning this seeder will not duplicate records with the same keys.
 * It does not delete or overwrite existing records.
 */
class DemoMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            // Units of material.
            $uoms = [];
            foreach ([
                ['PCS', 'Pieces'],
                ['SET', 'Set'],
                ['PAIR', 'Pair'],
            ] as [$code, $name]) {
                $uoms[$code] = UnitOfMaterial::firstOrCreate(
                    ['uom_code' => $code],
                    ['uom_name' => $name]
                );
            }

            // Fictional suppliers. Names/codes are deliberately different from the references shown by the user.
            $supplierData = [
                ['SUP-DEMO-AST', 'Pemasok Demo Aster'],
                ['SUP-DEMO-BRL', 'Pemasok Demo Boreal'],
                ['SUP-DEMO-CND', 'Pemasok Demo Candra'],
                ['SUP-DEMO-DHN', 'Pemasok Demo Dahana'],
            ];
            $suppliers = [];
            foreach ($supplierData as [$code, $name]) {
                $suppliers[$code] = Supplier::firstOrCreate(
                    ['supplier_code' => $code],
                    ['supplier_name' => $name]
                );
            }

            // Synthetic component, half-finished good, and finished-good part numbers.
            $partData = [
                ['CM-AST-401', 'Bracket Penahan A', 'Component', 'Komponen Demo', 'PCS'],
                ['CM-AST-402', 'Bracket Penahan B', 'Component', 'Komponen Demo', 'PCS'],
                ['CM-BRL-210', 'Plat Dudukan Mekanisme', 'Component', 'Komponen Demo', 'PCS'],
                ['CM-CLP-120', 'Klip Penahan Panel', 'Component', 'Komponen Demo', 'PCS'],
                ['CM-SPR-550', 'Pegas Pengembali', 'Component', 'Komponen Demo', 'PCS'],
                ['CM-PIN-310', 'Pin Penghubung Presisi', 'Component', 'Komponen Demo', 'PCS'],
                ['CM-RUB-230', 'Bantalan Karet Modular', 'Component', 'Komponen Demo', 'PCS'],
                ['CM-CVR-180', 'Cover Pelindung Bagian Dalam', 'Component', 'Komponen Demo', 'PCS'],
                ['HF-LCK-610', 'Submodul Pengunci Tipe A', 'HFG', 'Subassembly Demo', 'PCS'],
                ['HF-LCK-620', 'Submodul Pengunci Tipe B', 'HFG', 'Subassembly Demo', 'PCS'],
                ['HF-HND-630', 'Modul Pegangan Modular', 'HFG', 'Subassembly Demo', 'PCS'],
                ['FG-LCK-710', 'Unit Pengunci Tipe A', 'FG', 'Finished Good Demo', 'SET'],
                ['FG-LCK-720', 'Unit Pengunci Tipe B', 'FG', 'Finished Good Demo', 'SET'],
                ['FG-HND-730', 'Unit Pegangan Modular', 'FG', 'Finished Good Demo', 'SET'],
            ];
            $parts = [];
            foreach ($partData as [$number, $name, $type, $classification, $uomCode]) {
                $parts[$number] = Part::firstOrCreate(
                    ['part_number' => $number],
                    [
                        'part_name' => $name,
                        'part_type' => $type,
                        'classification' => $classification,
                        'uom_id' => $uoms[$uomCode]->uom_id,
                    ]
                );
            }

            // Box packing standard for each demo part.
            $packQty = [
                'CM-AST-401' => 40,
                'CM-AST-402' => 40,
                'CM-BRL-210' => 40,
                'CM-CLP-120' => 80,
                'CM-SPR-550' => 80,
                'CM-PIN-310' => 80,
                'CM-RUB-230' => 80,
                'CM-CVR-180' => 40,
                'HF-LCK-610' => 20,
                'HF-LCK-620' => 20,
                'HF-HND-630' => 20,
                'FG-LCK-710' => 10,
                'FG-LCK-720' => 10,
                'FG-HND-730' => 10,
            ];
            foreach ($packQty as $number => $qty) {
                BoxPackagingQuantity::firstOrCreate(
                    ['part_id' => $parts[$number]->part_id],
                    ['std_qty_per_box' => $qty]
                );
            }

            // Bill of Material: quantities required per one finished-good set.
            $bomRows = [
                ['FG-LCK-710', 'HF-LCK-610', 1],
                ['FG-LCK-710', 'CM-CLP-120', 2],
                ['FG-LCK-710', 'CM-SPR-550', 1],
                ['FG-LCK-710', 'CM-PIN-310', 2],
                ['FG-LCK-710', 'CM-RUB-230', 1],
                ['FG-LCK-720', 'HF-LCK-620', 1],
                ['FG-LCK-720', 'CM-CLP-120', 2],
                ['FG-LCK-720', 'CM-SPR-550', 1],
                ['FG-LCK-720', 'CM-PIN-310', 2],
                ['FG-LCK-720', 'CM-RUB-230', 1],
                ['FG-HND-730', 'HF-HND-630', 1],
                ['FG-HND-730', 'CM-AST-401', 1],
                ['FG-HND-730', 'CM-CVR-180', 1],
                ['FG-HND-730', 'CM-PIN-310', 2],
            ];
            foreach ($bomRows as [$fgNumber, $componentNumber, $qty]) {
                BillOfMaterial::firstOrCreate(
                    [
                        'fg_part_id' => $parts[$fgNumber]->part_id,
                        'component_part_id' => $parts[$componentNumber]->part_id,
                    ],
                    ['qty_per_unit' => $qty]
                );
            }

            // Process locations used by the flow in the Capstone diagram.
            $locationData = [
                ['Incoming Warehouse', 'Incoming Warehouse'],
                ['Incoming Rack', 'Incoming Rack'],
                ['Dandori', 'Dandori'],
                ['Assembly', 'Assembly'],
                ['Delivery', 'Delivery'],
                ['Outgoing Area', 'Outgoing Area'],
                ['Loading Area', 'Loading Area'],
            ];
            $locations = [];
            foreach ($locationData as [$name, $stage]) {
                $locations[$name] = Location::firstOrCreate(
                    ['location_name' => $name],
                    ['process_stage' => $stage, 'is_active' => true]
                );
            }

            // Fictional workforce records for the ManPower master page.
            $manpowerData = [
                ['DEMO-MP-WH-001', 'Raka Pradipta', 'Incoming Warehouse'],
                ['DEMO-MP-WH-002', 'Nisa Lestari', 'Incoming Warehouse'],
                ['DEMO-MP-RK-001', 'Tegar Mahesa', 'Incoming Rack'],
                ['DEMO-MP-RK-002', 'Sari Amarta', 'Incoming Rack'],
                ['DEMO-MP-DN-001', 'Arman Nusa', 'Dandori'],
                ['DEMO-MP-DN-002', 'Lila Prameswari', 'Dandori'],
                ['DEMO-MP-AS-001', 'Dava Pratama', 'Assembly'],
                ['DEMO-MP-AS-002', 'Kirana Putri', 'Assembly'],
                ['DEMO-MP-DL-001', 'Jati Wicaksana', 'Delivery'],
                ['DEMO-MP-DL-002', 'Mita Savira', 'Delivery'],
            ];
            foreach ($manpowerData as [$nik, $name, $stage]) {
                Manpower::firstOrCreate(
                    ['nik' => $nik],
                    ['manpower_name' => $name, 'process_stage' => $stage, 'manpower_status' => 'active']
                );
            }

            // A user with the Staf Administrasi role is required to own production plans and demo movements.
            $admin = User::where('username', 'admin')->first();
            if (!$admin) {
                $admin = User::whereHas('role', fn ($q) => $q->where('role_name', 'Staf Administrasi'))->first();
            }
            if (!$admin) {
                throw new RuntimeException('Akun Staf Administrasi belum ditemukan. Jalankan seeder akun dasar terlebih dahulu.');
            }

            // Planned quantities chosen to test the BOM after demo supplier boxes have been received and moved to Incoming Rack.
            $plans = [
                ['FG-LCK-710', 8],
                ['FG-LCK-720', 6],
                ['FG-HND-730', 5],
            ];
            foreach ($plans as [$fgNumber, $qty]) {
                ProductionPlan::firstOrCreate(
                    [
                        'fg_part_id' => $parts[$fgNumber]->part_id,
                        'created_by' => $admin->user_id,
                        'plan_date' => now()->toDateString(),
                    ],
                    ['qty_plan' => $qty, 'plan_status' => 'planned']
                );
            }

            // IMPORTANT: Do not seed material_boxes or stock here.
            // The agreed workflow starts when a supplier box arrives:
            // scan supplier QR -> auto-fill Incoming Warehouse -> operator verifies and saves
            // -> the same supplier QR is scanned at Incoming Rack to move the box.
            // This keeps the demo faithful to the process flow and avoids pre-registering boxes.
        });

        $this->command?->info('Master data demo berhasil ditambahkan: UOM, supplier, material/part, packaging quantity, BOM, Production Plan, ManPower, dan lokasi proses. Box/stok tidak dibuat otomatis; buat melalui scan QR supplier pada Incoming Warehouse sesuai flowchart. Data lama tidak dihapus.');
    }
}
