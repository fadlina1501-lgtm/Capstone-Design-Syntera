@extends('layouts.app')
@section('content')
<div class="page-head"><div><h1>{{ $row ? 'Ubah' : 'Tambah' }} {{ $config['title'] }}</h1><p>Lengkapi data sesuai kondisi master data yang digunakan dalam rancangan.</p></div><a class="btn secondary" href="{{ route('master.index',$entity) }}">← Kembali</a></div>
<div class="card"><form action="{{ $action }}" method="POST">@csrf @if($method === 'PUT')@method('PUT')@endif<div class="form-grid">
@foreach($config['fields'] as $field)<div class="field"><label for="f_{{ $field['name'] }}">{{ $field['label'] }} @if($field['required'] ?? false)<span style="color:#b42318">*</span>@endif</label>
@if($field['type'] === 'select')<select id="f_{{ $field['name'] }}" name="{{ $field['name'] }}" {{ ($field['required'] ?? false) ? 'required' : '' }}><option value="">Pilih {{ strtolower($field['label']) }}</option>@foreach($options[$field['name']] as $value=>$label)<option value="{{ $value }}" @selected((string)old($field['name'],$row->{$field['name']} ?? '') === (string)$value)>{{ $label }}</option>@endforeach</select>
@else<input id="f_{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}" value="{{ old($field['name'],$row->{$field['name']} ?? '') }}" {{ ($field['required'] ?? false) ? 'required' : '' }} @if(isset($field['max'])) maxlength="{{ $field['max'] }}" @endif @if(isset($field['step'])) step="{{ $field['step'] }}" @endif @if(isset($field['min'])) min="{{ $field['min'] }}" @endif @if($field['type']==='number') min="{{ $field['min'] ?? 0 }}" @endif>@endif
@if(isset($field['help']))<small>{{ $field['help'] }}</small>@endif</div>@endforeach
</div><div class="form-actions"><button type="submit" class="btn">{{ $row ? 'Simpan Perubahan' : 'Simpan Data' }}</button><a class="btn secondary" href="{{ route('master.index',$entity) }}">Batal</a></div></form></div>
@endsection
