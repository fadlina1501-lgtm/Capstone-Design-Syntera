<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $table = 'stock_opnames';
    protected $primaryKey = 'sto_id';
    public $timestamps = false;

    protected $fillable = [
        'sto_period',
        'sto_area',
        'sto_date',
        'sto_status',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'recount_of_sto_id',
        'import_time',
    ];

    protected $casts = [
        'sto_date' => 'date',
        'reviewed_at' => 'datetime',
        'import_time' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function details()
    {
        return $this->hasMany(StoDetail::class, 'sto_id', 'sto_id');
    }

    public function recountParent()
    {
        return $this->belongsTo(self::class, 'recount_of_sto_id', 'sto_id');
    }

    public function recounts()
    {
        return $this->hasMany(self::class, 'recount_of_sto_id', 'sto_id');
    }
}
