<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderItem;
use App\Models\IngredientBatch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    // GET /api/owner/profit/category?category=Cakes&from=YYYY-MM-DD&to=YYYY-MM-DD
    public function profitByCategory(Request $request)
    {
        $ownerId  = $request->user()->id;
        $category = $request->query('category', 'Cakes');
        $from     = $request->query('from');
        $to       = $request->query('to');

        // --- SALES (qualify columns) ---
        $salesQ = OrderItem::query()
            ->where('order_items.owner_id', $ownerId)
            ->whereHas('product', function ($qq) use ($category) {
                $qq->where('products.category', $category);
            });

        if ($from) $salesQ->whereDate('order_items.created_at', '>=', $from);
        if ($to)   $salesQ->whereDate('order_items.created_at', '<=', $to);

        $sales = $salesQ->select([
            DB::raw('SUM(order_items.quantity) as qty'),
            DB::raw('SUM(order_items.line_total) as revenue'),
        ])->first();

        $qty     = (int)   ($sales->qty ?? 0);
        $revenue = (float) ($sales->revenue ?? 0.0);

        // --- INGREDIENT COST (qualify columns) ---
        $batchesQ = IngredientBatch::query()
            ->where('ingredient_batches.owner_id', $ownerId)
            ->where('ingredient_batches.category', $category);

        if ($from) {
            // batch overlaps period if (period_end is null OR period_end >= from)
            $batchesQ->where(function ($qq) use ($from) {
                $qq->whereNull('ingredient_batches.period_end')
                   ->orWhere('ingredient_batches.period_end', '>=', $from);
            });
        }
        if ($to) {
            // overlaps if (period_start is null OR period_start <= to)
            $batchesQ->where(function ($qq) use ($to) {
                $qq->whereNull('ingredient_batches.period_start')
                   ->orWhere('ingredient_batches.period_start', '<=', $to);
            });
        }

        $batchIds = $batchesQ->pluck('id');

        $ingredientCost = 0.0;
        if ($batchIds->count()) {
            $ingredientCost = (float) DB::table('ingredient_batch_items')
                ->whereIn('ingredient_batch_items.batch_id', $batchIds)
                ->sum('ingredient_batch_items.line_cost');
        }

        $profit = $revenue - $ingredientCost;
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

        return response()->json([
            'category'        => $category,
            'period'          => ['from' => $from, 'to' => $to],
            'sold_quantity'   => $qty,
            'revenue'         => round($revenue, 2),
            'ingredient_cost' => round($ingredientCost, 2),
            'profit'          => round($profit, 2),
            'margin_pct'      => $margin,
        ]);
    }

    // GET /api/owner/profit/summary?from=YYYY-MM-DD&to=YYYY-MM-DD
    public function profitSummary(Request $request)
    {
        $ownerId = $request->user()->id;
        $from    = $request->query('from');
        $to      = $request->query('to');

        // --- SALES per category (fully-qualify everything) ---
        $sales = OrderItem::query()
            ->where('order_items.owner_id', $ownerId)
            ->when($from, fn ($q) => $q->whereDate('order_items.created_at', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('order_items.created_at', '<=', $to))
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->groupBy('products.category')
            ->selectRaw('products.category as category, SUM(order_items.quantity) as qty, SUM(order_items.line_total) as revenue')
            ->get()
            ->keyBy('category');

        // --- COST per category from batches overlapping the period ---
        $batches = IngredientBatch::query()
            ->where('ingredient_batches.owner_id', $ownerId)
            ->when($from, fn ($q) => $q->where(function ($qq) use ($from) {
                $qq->whereNull('ingredient_batches.period_end')
                   ->orWhere('ingredient_batches.period_end', '>=', $from);
            }))
            ->when($to, fn ($q) => $q->where(function ($qq) use ($to) {
                $qq->whereNull('ingredient_batches.period_start')
                   ->orWhere('ingredient_batches.period_start', '<=', $to);
            }))
            ->get();

        $idsByCategory = [];
        foreach ($batches as $b) {
            $idsByCategory[$b->category] = $idsByCategory[$b->category] ?? [];
            $idsByCategory[$b->category][] = $b->id;
        }

        $costByCategory = [];
        foreach ($idsByCategory as $cat => $ids) {
            $costByCategory[$cat] = (float) DB::table('ingredient_batch_items')
                ->whereIn('ingredient_batch_items.batch_id', $ids)
                ->sum('ingredient_batch_items.line_cost');
        }

        // Merge sales + costs
        $allCats = collect(array_unique(array_merge($sales->keys()->all(), array_keys($costByCategory))));
        $out = $allCats->map(function ($cat) use ($sales, $costByCategory) {
            $qty   = (int)   ($sales[$cat]->qty ?? 0);
            $rev   = (float) ($sales[$cat]->revenue ?? 0);
            $cost  = (float) ($costByCategory[$cat] ?? 0);
            $profit= $rev - $cost;
            $margin= $rev > 0 ? round(($profit / $rev) * 100, 2) : 0;

            return [
                'category'   => $cat,
                'quantity'   => $qty,
                'revenue'    => round($rev, 2),
                'cost'       => round($cost, 2),
                'profit'     => round($profit, 2),
                'margin_pct' => $margin,
            ];
        })->values();

        return response()->json([
            'period' => compact('from', 'to'),
            'data'   => $out,
        ]);
    }



    // GET /api/owner/dashboard

       public function dashboard(Request $request)
       {
           $user = $request->user();
           abort_unless($user, 401, 'Unauthenticated.');

           // validate & normalize date range
           $request->validate([
               'from' => ['nullable', 'date'],
               'to'   => ['nullable', 'date'],
           ]);

           $from = $request->filled('from')
               ? Carbon::parse($request->query('from'))->startOfDay()
               : now()->subDays(30)->startOfDay();

           $to = $request->filled('to')
               ? Carbon::parse($request->query('to'))->endOfDay()
               : now()->endOfDay();

           // ------- Revenue (your products only) -------
           // Join orders -> order_items -> products and filter by products.owner_id
           $salesAgg = DB::table('order_items as oi')
               ->join('orders as o', 'oi.order_id', '=', 'o.id')
               ->join('products as p', 'oi.product_id', '=', 'p.id')
               ->where('p.owner_id', $user->id)
               ->whereBetween('o.created_at', [$from, $to])
               ->selectRaw('
                   COALESCE(SUM(oi.line_total), 0)      as revenue,
                   COALESCE(SUM(oi.quantity), 0)        as units_sold,
                   COUNT(DISTINCT oi.product_id)        as distinct_products_sold
               ')
               ->first();

           $revenue                = (float) ($salesAgg->revenue ?? 0);
           $unitsSold              = (int)   ($salesAgg->units_sold ?? 0);
           $distinctProductsSold   = (int)   ($salesAgg->distinct_products_sold ?? 0);

           // ------- Top products by qty -------
           $topProducts = DB::table('order_items as oi')
               ->join('orders as o', 'oi.order_id', '=', 'o.id')
               ->join('products as p', 'oi.product_id', '=', 'p.id')
               ->where('p.owner_id', $user->id)
               ->whereBetween('o.created_at', [$from, $to])
               ->groupBy('oi.product_id', 'p.name')
               ->orderByDesc(DB::raw('SUM(oi.quantity)'))
               ->limit(5)
               ->get([
                   'oi.product_id',
                   'p.name',
                   DB::raw('SUM(oi.quantity) as qty'),
                   DB::raw('SUM(oi.line_total) as revenue'),
               ]);

           // ------- Ingredient spend (uses batches.total_cost) -------
           // If you store period in period_from/period_to, uncomment the period-based filter and comment created_at filter.
           $ingredientSpend = DB::table('ingredient_batches as b')
               ->where('b.owner_id', $user->id)
               ->whereBetween('b.created_at', [$from, $to])   // simple: use created_at window
               // ->where(function ($q) use ($from, $to) {     // alternative: use period window
               //     $q->whereBetween('b.period_from', [$from, $to])
               //       ->orWhereBetween('b.period_to', [$from, $to]);
               // })
               ->sum('b.total_cost');

           $profit = $revenue - (float) $ingredientSpend;

           return response()->json([
               'range' => [
                   'from' => $from->toDateTimeString(),
                   'to'   => $to->toDateTimeString(),
               ],
               'kpis' => [
                   'revenue'                 => round($revenue, 2),
                   'spend'                   => round((float) $ingredientSpend, 2),
                   'profit'                  => round($profit, 2),
                   'units_sold'              => $unitsSold,
                   'distinct_products_sold'  => $distinctProductsSold,
               ],
               'top_products' => $topProducts,
           ]);

       }
   }


