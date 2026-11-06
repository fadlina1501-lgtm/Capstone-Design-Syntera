<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreparationDetail extends Model
{
    protected $table = 'preparation_details';
    protected $primaryKey = 'preparation_detail_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['preparation_id', 'box_id', 'qty_prepared'];

    public function preparation() { return $this->belongsTo(Preparation::class, 'preparation_id', 'preparation_id'); }
    public function box() { return $this->belongsTo(MaterialBox::class, 'box_id', 'box_id'); }
}
