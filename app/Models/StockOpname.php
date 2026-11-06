<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $table = 'stock_opnames';
    protected $primaryKey = 'sto_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['sto_period', 'sto_date', 'sto_status', 'created_by', 'import_time'];

    public function creator() { return $this->belongsTo(User::class, 'created_by', 'user_id'); }
    public function details() { return $this->hasMany(StoDetail::class, 'sto_id', 'sto_id'); }
}
