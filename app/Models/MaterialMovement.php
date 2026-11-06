<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialMovement extends Model
{
    protected $table = 'material_movements';
    protected $primaryKey = 'movement_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['box_id', 'user_id', 'from_location_id', 'to_location_id', 'movement_type', 'quantity', 'scan_time'];

    public function box() { return $this->belongsTo(MaterialBox::class, 'box_id', 'box_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
    public function fromLocation() { return $this->belongsTo(Location::class, 'from_location_id', 'location_id'); }
    public function toLocation() { return $this->belongsTo(Location::class, 'to_location_id', 'location_id'); }
}
