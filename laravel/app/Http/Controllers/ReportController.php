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
           $ownerId = $user->id;

           // Validate dates coming from the UI
           $request->validate([
               'from' => ['nullable', 'date'],
               'to'   => ['nullable', 'date'],
           ]);

           // Normalize to full-day window
           $from = $request->filled('from')
               ? Carbon::parse($request->query('from'))->startOfDay()
               : now()->startOfMonth()->startOfDay();

           $to = $request->filled('to')
               ? Carbon::parse($request->query('to'))->endOfDay()
               : now()->endOfDay();

           // ---------------------------
           // SALES (owner products only)
           // ---------------------------
           // order_items: product_id, quantity, line_total
           // orders: created_at
           // products: owner_id, name, category
           $salesAgg = DB::table('order_items as oi')
               ->join('orders as o', 'oi.order_id', '=', 'o.id')
               ->join('products as p', 'oi.product_id', '=', 'p.id')
               ->where('p.owner_id', $ownerId)
               ->whereBetween('o.created_at', [$from, $to])
               ->selectRaw('
                   COALESCE(SUM(oi.line_total), 0) as revenue,
                   COALESCE(COUNT(DISTINCT o.id), 0) as orders_count
               ')
               ->first();

           $sales_amount = (float)($salesAgg->revenue ?? 0);
           $sales_count  = (int)($salesAgg->orders_count ?? 0);

           // Breakdown by product
           $product_breakdown = DB::table('order_items as oi')
               ->join('orders as o', 'oi.order_id', '=', 'o.id')
               ->join('products as p', 'oi.product_id', '=', 'p.id')
               ->where('p.owner_id', $ownerId)
               ->whereBetween('o.created_at', [$from, $to])
               ->groupBy('p.name')
               ->selectRaw('
                   p.name as product,
                   COALESCE(SUM(oi.quantity), 0) as qty,
                   COALESCE(SUM(oi.line_total), 0) as revenue
               ')
               ->orderByDesc('qty')
               ->limit(10)
               ->get();

           // Breakdown by category
           $category_breakdown = DB::table('order_items as oi')
               ->join('orders as o', 'oi.order_id', '=', 'o.id')
               ->join('products as p', 'oi.product_id', '=', 'p.id')
               ->where('p.owner_id', $ownerId)
               ->whereBetween('o.created_at', [$from, $to])
               ->groupBy('p.category')
               ->selectRaw('
                   p.category as category,
                   COALESCE(SUM(oi.quantity), 0) as qty,
                   COALESCE(SUM(oi.line_total), 0) as revenue
               ')
               ->orderByDesc('revenue')
               ->get();

           // --------------------------------------------
           // INGREDIENTS (usage & cost from batch items)
           // --------------------------------------------
           // ingredient_batches: id, owner_id, period_start, period_end, created_at
           // ingredient_batch_items: batch_id, ingredient_id, quantity_used, unit_price_snapshot
           // ingredients: id, name, unit
           //
           // Include a batch if its [period_start, period_end] overlaps the selected window.
           // If period_* are NULL (old rows), fall back to created_at in the same window.
           $ingredient_usage = DB::table('ingredient_batch_items as ibi')
               ->join('ingredient_batches as b', 'ibi.batch_id', '=', 'b.id')
               ->leftJoin('ingredients as ing', 'ibi.ingredient_id', '=', 'ing.id')
               ->where('b.owner_id', $ownerId)
               ->where(function ($q) use ($from, $to) {
                   $q->where(function ($q1) use ($from, $to) {
                       // Overlap on period range
                       $q1->whereNotNull('b.period_start')
                           ->whereNotNull('b.period_end')
                           ->where(function ($qq) use ($from, $to) {
                               $qq->whereBetween('b.period_start', [$from, $to])
                                  ->orWhereBetween('b.period_end', [$from, $to])
                                  ->orWhere(function ($qqq) use ($from, $to) {
                                      $qqq->where('b.period_start', '<=', $from)
                                          ->where('b.period_end', '>=', $to);
                                  });
                           });
                   })
                   ->orWhere(function ($q2) use ($from, $to) {
                       // Fallback to created_at window for legacy rows
                       $q2->whereNull('b.period_start')
                          ->orWhereNull('b.period_end')
                          ->whereBetween('b.created_at', [$from, $to]);
                   });
               })
               ->groupBy('ing.name', 'ing.unit')
               ->selectRaw('
                   COALESCE(ing.name, "Unknown") as ingredient,
                   COALESCE(ing.unit, "") as unit,
                   COALESCE(SUM(ibi.quantity_used), 0) as quantity,
                   COALESCE(SUM(ibi.quantity_used * ibi.unit_price_snapshot), 0) as cost
               ')
               ->orderByDesc('cost')
               ->get();

           // Total ingredient cost (from items). If 0, try summing batches.total_cost if you’ve added that column.
           $ingredient_cost = (float) DB::table('ingredient_batch_items as ibi')
               ->join('ingredient_batches as b', 'ibi.batch_id', '=', 'b.id')
               ->where('b.owner_id', $ownerId)
               ->where(function ($q) use ($from, $to) {
                   $q->where(function ($q1) use ($from, $to) {
                       $q1->whereNotNull('b.period_start')
                           ->whereNotNull('b.period_end')
                           ->where(function ($qq) use ($from, $to) {
                               $qq->whereBetween('b.period_start', [$from, $to])
                                  ->orWhereBetween('b.period_end', [$from, $to])
                                  ->orWhere(function ($qqq) use ($from, $to) {
                                      $qqq->where('b.period_start', '<=', $from)
                                          ->where('b.period_end', '>=', $to);
                                  });
                           });
                   })
                   ->orWhere(function ($q2) use ($from, $to) {
                       $q2->whereNull('b.period_start')
                          ->orWhereNull('b.period_end')
                          ->whereBetween('b.created_at', [$from, $to]);
                   });
               })
               ->selectRaw('COALESCE(SUM(ibi.quantity_used * ibi.unit_price_snapshot), 0) as spend')
               ->value('spend');

           // Optional fallback: if you created `ingredient_batches.total_cost` and keep it updated
           if ($ingredient_cost <= 0) {
               $ingredient_cost = (float) DB::table('ingredient_batches as b')
                   ->where('b.owner_id', $ownerId)
                   ->where(function ($q) use ($from, $to) {
                       $q->where(function ($q1) use ($from, $to) {
                           $q1->whereNotNull('b.period_start')
                               ->whereNotNull('b.period_end')
                               ->where(function ($qq) use ($from, $to) {
                                   $qq->whereBetween('b.period_start', [$from, $to])
                                      ->orWhereBetween('b.period_end', [$from, $to])
                                      ->orWhere(function ($qqq) use ($from, $to) {
                                          $qqq->where('b.period_start', '<=', $from)
                                              ->where('b.period_end', '>=', $to);
                                      });
                               });
                       })
                       ->orWhere(function ($q2) use ($from, $to) {
                           $q2->whereNull('b.period_start')
                              ->orWhereNull('b.period_end')
                              ->whereBetween('b.created_at', [$from, $to]);
                       });
                   })
                   ->sum('b.total_cost');
           }

           $profit = $sales_amount - $ingredient_cost;

           return response()->json([
               // keep these keys because your React expects them
               'sales_count'         => $sales_count,
               'sales_amount'        => round($sales_amount, 2),
               'product_breakdown'   => $product_breakdown,
               'category_breakdown'  => $category_breakdown,
               'ingredient_usage'    => $ingredient_usage,
               'ingredient_cost'     => round($ingredient_cost, 2),
               'profit'              => round($profit, 2),
               // echo back the normalized range
               'from' => $from->toDateString(),
               'to'   => $to->toDateString(),
           ]);
       }

   }


