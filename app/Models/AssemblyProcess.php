<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssemblyProcess extends Model
{
    protected $table = 'assembly_processes';
    protected $primaryKey = 'assembly_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['preparation_id', 'user_id', 'qty_produced', 'assembly_time'];

    public function preparation() { return $this->belongsTo(Preparation::class, 'preparation_id', 'preparation_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
    public function boxes() { return $this->hasMany(MaterialBox::class, 'assembly_id', 'assembly_id'); }
}
