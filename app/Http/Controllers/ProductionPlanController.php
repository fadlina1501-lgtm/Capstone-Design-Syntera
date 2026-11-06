<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['plan_date' => ['nullable', 'date']]);

        $producedByPlan = DB::table('assembly_processes as a')
            ->join('preparations as prep', 'prep.preparation_id', '=', 'a.preparation_id')
            ->select('prep.plan_id', DB::raw('SUM(a.qty_produced) as qty_produced'))
            ->groupBy('prep.plan_id')
            ->pluck('qty_produced', 'plan_id');

        $plans = ProductionPlan::with('finishedGood')
            ->when($request->filled('plan_date'), fn ($query) => $query->whereDate('plan_date', $request->plan_date))
            ->orderByDesc('plan_date')
            ->orderBy('plan_id')
            ->paginate(20)
            ->withQueryString();

        return view('production-plans.index', compact('plans', 'producedByPlan'));
    }
}
