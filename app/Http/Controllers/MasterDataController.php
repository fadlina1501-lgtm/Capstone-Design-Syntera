<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    private function definitions(): array
    {
        return [
            'units' => ['title'=>'Unit of Material','table'=>'unit_of_materials','pk'=>'uom_id','columns'=>['uom_code','uom_name'],'unique'=>['uom_code'], 'fields'=>[
                ['name'=>'uom_code','label'=>'Kode UOM','type'=>'text','required'=>true,'max'=>20],
                ['name'=>'uom_name','label'=>'Nama satuan','type'=>'text','required'=>true,'max'=>60],
            ]],
            'parts' => ['title'=>'Part / Material','table'=>'parts','pk'=>'part_id','columns'=>['part_number','part_name','part_type','classification','uom_id'],'unique'=>['part_number'],'fields'=>[
                ['name'=>'part_number','label'=>'Part number','type'=>'text','required'=>true,'max'=>80],
                ['name'=>'part_name','label'=>'Nama part','type'=>'text','required'=>true,'max'=>150],
                ['name'=>'part_type','label'=>'Jenis part','type'=>'select','required'=>true,'options'=>['FG'=>'Finished Good (FG)','HFG'=>'Half Finished Good (HFG)','Component'=>'Component']],
                ['name'=>'classification','label'=>'Klasifikasi','type'=>'text','required'=>false,'max'=>60],
                ['name'=>'uom_id','label'=>'Satuan','type'=>'select','required'=>true,'lookup'=>'unit_of_materials','value'=>'uom_id','label_field'=>'uom_name'],
            ]],
            'packaging-quantities' => ['title'=>'Box Packaging Quantity','table'=>'box_packaging_quantities','pk'=>'box_packaging_id','columns'=>['part_id','std_qty_per_box'],'unique'=>['part_id'],'fields'=>[
                ['name'=>'part_id','label'=>'Part','type'=>'select','required'=>true,'lookup'=>'parts','value'=>'part_id','label_field'=>'part_number'],
                ['name'=>'std_qty_per_box','label'=>'Jumlah standar per box','type'=>'number','required'=>true,'step'=>'0.001','min'=>0.001],
            ]],
            'bom' => ['title'=>'Bill of Material (BOM)','table'=>'bill_of_materials','pk'=>'bom_id','columns'=>['fg_part_id','component_part_id','qty_per_unit'],'unique'=>[],'fields'=>[
                ['name'=>'fg_part_id','label'=>'Part Finished Good','type'=>'select','required'=>true,'lookup'=>'parts','value'=>'part_id','label_field'=>'part_number','filter'=>['part_type'=>'FG']],
                ['name'=>'component_part_id','label'=>'Part komponen','type'=>'select','required'=>true,'lookup'=>'parts','value'=>'part_id','label_field'=>'part_number','filter_not'=>['part_type'=>'FG']],
                ['name'=>'qty_per_unit','label'=>'Kebutuhan per unit FG','type'=>'number','required'=>true,'step'=>'0.0001','min'=>0.0001],
            ]],
            'suppliers' => ['title'=>'Supplier','table'=>'suppliers','pk'=>'supplier_id','columns'=>['supplier_code','supplier_name'],'unique'=>['supplier_code'],'fields'=>[
                ['name'=>'supplier_code','label'=>'Kode supplier','type'=>'text','required'=>true,'max'=>40],
                ['name'=>'supplier_name','label'=>'Nama supplier','type'=>'text','required'=>true,'max'=>120],
            ]],
            'production-plans' => ['title'=>'Production Plan','table'=>'production_plans','pk'=>'plan_id','columns'=>['fg_part_id','plan_date','qty_plan','plan_status','created_by'],'unique'=>[],'fields'=>[
                ['name'=>'fg_part_id','label'=>'Part Finished Good','type'=>'select','required'=>true,'lookup'=>'parts','value'=>'part_id','label_field'=>'part_number','filter'=>['part_type'=>'FG']],
                ['name'=>'plan_date','label'=>'Tanggal rencana','type'=>'date','required'=>true],
                ['name'=>'qty_plan','label'=>'Jumlah rencana','type'=>'number','required'=>true,'step'=>'0.001','min'=>0.001],
                ['name'=>'plan_status','label'=>'Status rencana','type'=>'select','required'=>true,'options'=>['planned'=>'Planned','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled']],
            ]],
            'manpowers' => ['title'=>'ManPower','table'=>'manpowers','pk'=>'manpower_id','columns'=>['nik','manpower_name','process_stage','manpower_status'],'unique'=>['nik'],'fields'=>[
                ['name'=>'nik','label'=>'NIK','type'=>'text','required'=>true,'max'=>40],
                ['name'=>'manpower_name','label'=>'Nama manpower','type'=>'text','required'=>true,'max'=>120],
                ['name'=>'process_stage','label'=>'Tahapan proses','type'=>'select','required'=>true,'options'=>['Incoming Warehouse'=>'Incoming Warehouse','Incoming Rack'=>'Incoming Rack','Dandori'=>'Dandori','Assembly'=>'Assembly','Delivery'=>'Delivery']],
                ['name'=>'manpower_status','label'=>'Status','type'=>'select','required'=>true,'options'=>['active'=>'Aktif','inactive'=>'Tidak aktif']],
            ]],
            'locations' => ['title'=>'Lokasi proses','table'=>'locations','pk'=>'location_id','columns'=>['location_name','process_stage','is_active'],'unique'=>['location_name'],'fields'=>[
                ['name'=>'location_name','label'=>'Nama lokasi','type'=>'text','required'=>true,'max'=>80],
                ['name'=>'process_stage','label'=>'Tahapan proses','type'=>'select','required'=>true,'options'=>['Incoming Warehouse'=>'Incoming Warehouse','Incoming Rack'=>'Incoming Rack','Dandori'=>'Dandori','Assembly'=>'Assembly','Delivery'=>'Delivery','Outgoing Area'=>'Outgoing Area','Loading Area'=>'Loading Area','Outbound'=>'Outbound']],
                ['name'=>'is_active','label'=>'Aktif','type'=>'select','required'=>true,'options'=>['1'=>'Ya','0'=>'Tidak']],
            ]],
        ];
    }

    private function config(string $entity): array
    {
        $all = $this->definitions();
        abort_unless(isset($all[$entity]), 404, 'Master data tidak ditemukan.');
        return $all[$entity];
    }

    private function fieldOptions(array $field): array
    {
        if (isset($field['options'])) return $field['options'];
        if (!isset($field['lookup'])) return [];
        $query = DB::table($field['lookup']);
        foreach (($field['filter'] ?? []) as $column => $value) $query->where($column, $value);
        foreach (($field['filter_not'] ?? []) as $column => $value) $query->where($column, '!=', $value);
        return $query->orderBy($field['label_field'])->pluck($field['label_field'], $field['value'])->toArray();
    }

    private function viewData(string $entity, array $config): array
    {
        $options = [];
        foreach ($config['fields'] as $field) $options[$field['name']] = $this->fieldOptions($field);
        $displayMaps = [];
        foreach ($config['fields'] as $field) if (isset($field['lookup'])) $displayMaps[$field['name']] = $options[$field['name']];
        return compact('entity','config','options','displayMaps');
    }

    public function index(string $entity)
    {
        $config = $this->config($entity);
        $data = $this->viewData($entity, $config);
        $rows = DB::table($config['table'])->orderByDesc($config['pk'])->paginate(15);
        return view('master.index', $data + compact('rows'));
    }

    public function create(string $entity)
    {
        $config = $this->config($entity);
        return view('master.form', $this->viewData($entity, $config) + ['row'=>null,'action'=>route('master.store',$entity),'method'=>'POST']);
    }

    public function store(Request $request, string $entity)
    {
        $config = $this->config($entity);
        abort_unless($request->user()->role?->role_name === 'Staf Administrasi', 403);
        $data = $this->validatedData($request, $config, $entity);
        if ($entity === 'production-plans') $data['created_by'] = $request->user()->user_id;
        if ($entity === 'locations') $data['is_active'] = (bool) $data['is_active'];
        $id = DB::table($config['table'])->insertGetId($data, $config['pk']);
        AuditLogger::log($request->user()->user_id, 'Tambah master data '.$config['title'].' #'.$id, $request);
        return redirect()->route('master.index',$entity)->with('status','Data berhasil ditambahkan.');
    }

    public function edit(string $entity, int $id)
    {
        $config = $this->config($entity);
        $row = DB::table($config['table'])->where($config['pk'],$id)->first();
        abort_unless($row,404);
        return view('master.form', $this->viewData($entity, $config) + ['row'=>$row,'action'=>route('master.update',[$entity,$id]),'method'=>'PUT']);
    }

    public function update(Request $request, string $entity, int $id)
    {
        $config = $this->config($entity);
        abort_unless($request->user()->role?->role_name === 'Staf Administrasi', 403);
        $row = DB::table($config['table'])->where($config['pk'],$id)->first();
        abort_unless($row,404);
        $data = $this->validatedData($request, $config, $entity, $id);
        unset($data['created_by']);
        if ($entity === 'locations') $data['is_active'] = (bool) $data['is_active'];
        DB::table($config['table'])->where($config['pk'],$id)->update($data);
        AuditLogger::log($request->user()->user_id, 'Ubah master data '.$config['title'].' #'.$id, $request);
        return redirect()->route('master.index',$entity)->with('status','Data berhasil diperbarui.');
    }

    public function destroy(Request $request, string $entity, int $id)
    {
        $config = $this->config($entity);
        abort_unless($request->user()->role?->role_name === 'Staf Administrasi', 403);
        try {
            DB::table($config['table'])->where($config['pk'],$id)->delete();
        } catch (\Throwable $e) {
            return back()->withErrors(['delete'=>'Data tidak dapat dihapus karena masih digunakan oleh transaksi lain.']);
        }
        AuditLogger::log($request->user()->user_id, 'Hapus master data '.$config['title'].' #'.$id, $request);
        return redirect()->route('master.index',$entity)->with('status','Data berhasil dihapus.');
    }

    private function validatedData(Request $request, array $config, string $entity, ?int $id = null): array
    {
        $rules = [];
        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $base = ($field['required'] ?? false) ? 'required' : 'nullable';
            if (isset($field['options'])) $rule = [$base, Rule::in(array_keys($field['options']))];
            elseif (isset($field['lookup'])) $rule = [$base, 'integer', Rule::exists($field['lookup'], $field['value'])];
            elseif ($field['type'] === 'number') $rule = [$base, 'numeric', 'min:'.($field['min'] ?? 0)];
            elseif ($field['type'] === 'date') $rule = [$base, 'date'];
            else $rule = [$base, 'string', 'max:'.($field['max'] ?? 255)];
            if (in_array($name, $config['unique'] ?? [], true)) {
                $unique = Rule::unique($config['table'], $name);
                if ($id) $unique->ignore($id, $config['pk']);
                $rule[] = $unique;
            }
            $rules[$name] = $rule;
        }
        $validator = Validator::make($request->all(), $rules);
        if ($entity === 'bom') {
            $validator->after(function ($validator) use ($request, $id) {
                $query = DB::table('bill_of_materials')
                    ->where('fg_part_id', $request->input('fg_part_id'))
                    ->where('component_part_id', $request->input('component_part_id'));
                if ($id) $query->where('bom_id', '!=', $id);
                if ($query->exists()) $validator->errors()->add('component_part_id', 'Kombinasi Finished Good dan komponen ini sudah ada di BOM.');
                if ($request->input('fg_part_id') && $request->input('fg_part_id') === $request->input('component_part_id')) {
                    $validator->errors()->add('component_part_id', 'Finished Good tidak dapat menjadi komponen untuk dirinya sendiri.');
                }
            });
        }
        $validated = $validator->validate();
        foreach ($config['fields'] as $field) {
            if (($field['required'] ?? false) === false && empty($validated[$field['name']])) $validated[$field['name']] = null;
        }
        return $validated;
    }
}
