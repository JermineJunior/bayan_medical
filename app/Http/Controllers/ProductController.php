<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Unit;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use Illuminate\View\View;
use App\Models\StockDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('category')->orderBy('id', 'DESC')->get();
        $categories = Category::select('id', 'name')->get();
        return view('product.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::get();
        $stocks = Stock::get();
        $units = Unit::get();
        return view('product.create', compact('categories', 'stocks', 'units'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $barcode = $request->get('barcode') ?? rand(1000000, 999999999);

            // إنشاء المنتج
            $product = Product::create([
                'barcode' => $barcode,
                'name' => $request->name,
                'name_en' => $request->name_en,
                'category_id' => $request->category_id,
                'expire_date' => $request->expire_date,
                'unit_reorder_level' => $request->unit_reorder_level,
                'unit_qty' => $request->unit_opening_balance,
            ]);

            // تجميع بيانات الوحدات
            $units = collect($request->units)->mapWithKeys(function ($unit) {
                return [
                    $unit['unit_id'] => [
                        'price' => $unit['price'] ?? 0,
                        'conversion_factor' => $unit['conversion_factor'],
                        'price_purchase' => $unit['price_purchase'] ?? 0,
                    ],
                ];
            });

            // تعريف المتغيرات مسبقًا لتجنب الأخطاء
            $reorder_level = null;

            foreach ($request->units as $unit) {
                if ($unit['unit_id'] == $request->unit_reorder_level) {
                    $reorder_level = $request->reorder_level * $unit['conversion_factor'];
                }
            }

            // التحقق من أن جميع القيم المطلوبة موجودة
            $missingFields = [];
            if (is_null($reorder_level)) {
                $missingFields[] = 'الوحدة المستخدمة في حد الطلب غير متوفرة.';
            }

            if (!empty($missingFields)) {
                DB::rollBack();
                return redirect()
                    ->route('product.index')
                    ->withErrors(['error' => implode(' ', $missingFields)]);
            }

            // تحديث بيانات المنتج
            $product->update([
                'reorder_level' => $reorder_level,
            ]);

            // ربط المنتج مع الوحدات
            $product->units()->attach($units);

            $stocks = Stock::get();

            // إدراج بيانات المخازن لكل المخازن المرتبطة بالمنتج
            foreach ($stocks as $stock) {
                $product->stocks()->attach($stock->id, [
                    'quantity' => 0,
                    'unit_stock' => $product->units()->first()->pivot->unit_id,
                ]);
            }

            DB::commit();

            return redirect()->route('product.index')->with('success_created', 'تمت إضافة المنتج بنجاح.');
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()
                ->route('product.index')
                ->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function edit($id): View
    {
        $product = Product::with(['category', 'units', 'stocks'])->findOrFail($id);
        $categories = Category::get();
        $stocks = Stock::get();
        $units = Unit::get();
        foreach ($product->units as $unit) {
            if ($unit->pivot->unit_id == $product->unit_reorder_level) {
                $reorder_level = $product->reorder_level / $unit->pivot->conversion_factor;
            }
        }

        return view('product.edit', compact('product', 'categories', 'stocks', 'units'));
    }

    public function update(Product $product, UpdateProductRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            /* ===============================
             * 1️⃣ تحديث بيانات المنتج
             * =============================== */
            $barcode = $request->barcode ?? $product->barcode;

            $product->update([
                'barcode' => $barcode,
                'name' => $request->name,
                'name_en' => $request->name_en,
                'category_id' => $request->category_id,
                'expire_date' => $request->expire_date,
                'unit_purchase_price' => $request->unit_purchase_price,
                'unit_reorder_level' => $request->unit_reorder_level,
                'unit_qty' => $request->unit_opening_balance,
            ]);

            /* ===============================
             * 2️⃣ تجهيز وحدات المنتج (add / update / delete)
             * =============================== */
            $units = collect($request->units)
                ->mapWithKeys(function ($unit) {
                    return [
                        $unit['unit_id'] => [
                            'price' => $unit['price'] ?? 0,
                            'conversion_factor' => $unit['conversion_factor'],
                            'price_purchase' => $unit['price_purchase'] ?? 0,
                        ],
                    ];
                })
                ->toArray();

            // sync = إضافة + تحديث + حذف تلقائي
            $product->units()->sync($units);

            /* ===============================
             * 3️⃣ حساب reorder_level
             * =============================== */
            $reorder_level = null;

            foreach ($request->units as $unit) {
                if ($unit['unit_id'] == $request->unit_reorder_level) {
                    $reorder_level = $request->reorder_level * $unit['conversion_factor'];
                    break;
                }
            }

            if (is_null($reorder_level)) {
                DB::rollBack();
                return redirect()
                    ->route('product.index')
                    ->withErrors(['error' => 'الوحدة المستخدمة في حد الطلب غير موجودة']);
            }

            $product->update([
                'reorder_level' => $reorder_level,
            ]);

            /* ===============================
             * 4️⃣ ربط المنتج بالمخازن لو ما مربوط
             * =============================== */
            $productStocks = $product->stocks;

            if ($productStocks->isEmpty()) {
                $stocks = Stock::all();
                $firstUnit = $product->units()->first();

                foreach ($stocks as $stock) {
                    $product->stocks()->attach($stock->id, [
                        'quantity' => 0,
                        'unit_stock' => $firstUnit?->pivot->unit_id,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('product.index')->with('success_updated', 'تم تحديث المنتج بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('product.index')
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        $totalStock = (float) $product->stocks()->sum('quantity');

        if ($totalStock > 0) {
            return back()->withErrors('لا يمكن حذف هذا المنتج لانه الكمية الحالية في المخزن غير صفر');
        }

        DB::transaction(function () use ($product) {
            $product->stockDetails()->delete();
            $product->delete();
        });

        return back()->with('success_deleted', 'تم الحذف بنجاح ');
    }

    public function checkBarcode(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $exists = Product::where('id', '!=', $id)->where('barcode', $request->barcode)->exists(); // تحقق من وجود الباركود في قاعدة البيانات
        return response()->json(['isUnique' => !$exists]);
    }

    public function checkName(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $exists = Product::where(['name' => $request->name])
            ->where('id', '!=', $id)
            ->exists(); // تحقق من وجود الاسم في قاعدة البيانات
        return response()->json(['isUnique' => !$exists]); // إذا كانت النتيجة false تعني أن الاسم فريد
    }

    public function getProduct(Request $request): JsonResponse
    {
        $productName = $request->input('name'); // استخدام الاسم في الطلب

        // البحث عن المنتج مع الوحدات المرتبطة
        $product = Product::with(['units:id,name', 'stocks:id,name'])
            ->where(function ($query) use ($productName) {
                $query
                    ->where(column: 'name', operator: $productName) // البحث عن الاسم
                    ->orWhere(column: 'barcode', operator: $productName); // أو البحث عن الباركود
            })
            ->when($request->type != 1, function ($q) use ($request) {
                $q->whereHas('stocks', function ($query) use ($request) {
                    $query->where('stock_id', $request->stock_id);
                });
            })
            ->first(); // جلب أول منتج مطابق

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'لا يتوفر هذا المنتج',
            ]);
        }

        $requireStock = $request->boolean('require_stock');
        $availableStocks = $product->stocks()->wherePivot('quantity', '>', 0)->get(['stocks.id', 'stocks.name']);

        if ($requireStock && $availableStocks->isEmpty()) {
            return response()->json([
                'success' => false,
                'no_stock' => true,
                'message' => 'المنتج "' . $product->name . '" ليس له كمية في المخازن، لا يمكن اضافته للفاتورة',
            ]);
        }

        return response()->json([
            'success' => true,
            'product' => $product,
            'units' => $product->units,
            'stocks' => $requireStock ? $availableStocks : $product->stocks,
            'quantity' => $product->convertQuantity($product->stocks()->where('stock_id', $request->stock_id)->sum('quantity')),
            'qty_in_stock' => $availableStocks->first()?->pivot->quantity,
            'stock_from' => $product->stocks()->where('quantity', '>=', 0)->get(),
        ]);
    }

    public function getUnitPrice(Request $request): JsonResponse
    {
        $productId = $request->input('productId');
        $unitId = $request->input('unitId');
        $stockId = $request->input('stockId');
        $product = Product::with('units', 'stocks')->whereId($productId)->first();
        $quantity = $product->stocks()->where('stock_id', $stockId)->first()->pivot->quantity;

        foreach ($product->units as $unit) {
            if ($unit->id == $unitId) {
                return response()->json([
                    'success' => true,
                    'price' => $unit->pivot->price,
                    'price_purchase' => $unit->pivot->price_purchase,
                    'firstPrice' => $product->units[0]->pivot->price,
                    'qty_in_stock' => $quantity,
                ]);
            }
        }
        return response()->json([
            'success' => false,
            'message' => 'الوحدة غير متوفرة',
        ]);
    }

    public function checkQty(Request $request): JsonResponse
    {
        $qty = $request->input('qty', 1);
        $bounce = $request->input('bounce', 0);
        $unitId = $request->input('unit_id');
        $productId = $request->input('product_id');
        $stockId = $request->input('stock_id');

        // جلب المنتج مع العلاقات المطلوبة
        $product = Product::with(['units', 'stocks'])->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود.',
            ]);
        }

        // التحقق من تاريخ الصلاحية
        if ($product->expire_date && Carbon::parse($product->expire_date)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المنتج منتهي الصلاحية.',
            ]);
        }

        $stock = $product->stocks->firstWhere('id', $stockId);
        $unit = $unitId ? $product->units->firstWhere('id', $unitId) : null;
        $conversionFactor = $unit ? $unit->pivot->conversion_factor : 1;

        $totalQty = ((float) $qty + (float) $bounce) * $conversionFactor;
        $availableQty = $stock ? $stock->pivot->quantity : 0;

        // التحقق من الكمية
        if ($totalQty > $availableQty) {
            $maxQty = floor(($availableQty / $conversionFactor) * 100) / 100;

            return response()->json([
                'success' => false,
                'max_qty' => $maxQty,
                'message' => 'الكمية المطلوبة أكبر من المتوفر، المتوفر: ' . $maxQty,
            ]);
        }

        return response()->json([
            'success' => true,
            'qty' => $totalQty,
            'product' => $product,
        ]);
    }

    public function opening(): View
    {
        $products = Product::with('stocks', 'units')->inRandomOrder()->take(5)->get();
        $stock_details = StockDetail::where('type', 7)->where('branch_id', session('branch_id'))->get();
        $stocks = Stock::get();
        foreach ($stock_details as $value) {
            $product = Product::with('units')->find($value->product_id);
            $unit = $product?->units()->firstWhere('unit_id', $value->unit_id);
            $conversion_factor = $unit?->pivot->conversion_factor ?: 1;
            $value->qty = $value->wared / $conversion_factor;
        }
        return view('product.open', compact('products', 'stock_details', 'stocks'));
    }

    public function opening_store(Request $request): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'product_id' => 'required|array',
                'product_id.*' => 'exists:products,id',
                'unit_id' => 'required|array',
                'unit_id.*' => 'exists:units,id',
                'stock_id' => 'required|array',
                'stock_id.*' => 'exists:stocks,id',
                'qty' => 'required|array',
                'qty.*' => 'numeric',
            ]);

            $productStockQunatity = StockDetail::where('branch_id', session('branch_id'))->where('type', 7)->get();

            StockDetail::where([
                'type' => 7,
                'branch_id' => session('branch_id'),
            ])->delete();
            foreach ($productStockQunatity as $item) {
                $product = Product::with(['stocks', 'units'])->find($item->product_id);
                if (!$product) {
                    continue;
                }

                $stock = $product->stocks()->where('stock_id', $item->stock_id)->first();

                if (!$stock) {
                    continue;
                }

                $qtyBack = $item->wared;
                $product->stocks()->updateExistingPivot($item->stock_id, ['quantity' => $stock->pivot->quantity - $qtyBack]);
            }

            $product_ids = array_values($request->product_id);
            $unit_ids = array_values($request->unit_id);
            $qtys = array_values($request->qty);
            $stock_ids = array_values($request->stock_id);

            $count = count($product_ids);
            $entries = [];

            // جمع السطور المتكررة للمنتج والمخزن
            for ($i = 0; $i < $count; $i++) {
                $key = $product_ids[$i] . '_' . $stock_ids[$i] . '_' . $unit_ids[$i];

                if (!isset($entries[$key])) {
                    $entries[$key] = [
                        'product_id' => $product_ids[$i],
                        'unit_id' => $unit_ids[$i],
                        'qty' => $qtys[$i],
                        'stock_id' => $stock_ids[$i],
                    ];
                } else {
                    $entries[$key]['qty'] += $qtys[$i];
                }
            }

            $stock_details = [];

            foreach ($entries as $entry) {
                $product = Product::with(['units', 'stocks'])->find($entry['product_id']);
                if (!$product) {
                    continue;
                }

                $unitIds = $product->units()->pluck('unit_id')->toArray();
                $stock = $product->stocks()->where('stock_id', $entry['stock_id'])->first();

                $units = collect($product->units)->mapWithKeys(function ($unit) {
                    return [
                        $unit->pivot['unit_id'] => [
                            'price' => $unit->pivot['price'],
                            'conversion_factor' => $unit->pivot['conversion_factor'],
                        ],
                    ];
                });

                $unitId = $entry['unit_id'];
                $quantity = $entry['qty'];
                $stockId = $entry['stock_id'];

                if (!in_array($unitId, $unitIds)) {
                    return redirect()
                        ->route('product.index')
                        ->withErrors(['error' => 'خطأ! بعض الوحدات غير موجودة للمنتج.']);
                }

                $conversion_factor = $units[$unitId]['conversion_factor'] ?? 1;
                $new_quantity = $quantity * $conversion_factor;

                // تحديث المنتج في المخزن (نضع الكمية مباشرة)
                $product->stocks()->updateExistingPivot($stockId, [
                    'quantity' => $stock->pivot->quantity + $new_quantity,
                ]);

                $stock_details[] = [
                    'product_id' => $product->id,
                    'wared' => $new_quantity,
                    'sader' => 0,
                    'stock_id' => $stockId,
                    'type' => 7,
                    'unit_id' => $unitId,
                    'user_id' => Auth::id(),
                    'branch_id' => session('branch_id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            StockDetail::insert($stock_details);
            DB::commit();

            return redirect()->back()->with('success_created', 'تمت الإضافة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('حدث خطا ما ');
        }
    }

    public function updateQty(Request $request)
    {
        foreach ($request->deleted_products as $product) {
            StockDetail::where(['type' => 7, 'product_id' => $product['id'], 'stock_id' => $product['stockId'], 'branch_id' => session('branch_id')])
                ->whereYear('created_at', Carbon::now()->year)
                ->delete();
            $productqty = Product::with('units', 'stocks')->firstWhere('id', $product['id']);
            $conversion_factor = $productqty->units()->firstWhere('unit_id', $product['unitId'])->pivot->conversion_factor;
            $qty = $product['qty'] * $conversion_factor;
            $productStock = $productqty->stocks->where('id', $product['stockId'])->first();
            $newQuantity = $productStock->pivot->quantity - $qty;
            $productqty->stocks()->syncWithoutDetaching([
                $product['stockId'] => ['quantity' => $newQuantity],
            ]);
        }
        return response()->json(['success' => true, 'message' => 'تم تحديث الكمية بنجاح']);
    }

    public function excel_create()
    {
        return view('product.excel');
    }

    public function excel_store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new ProductsImport(), $request->file('file'));
            return redirect()->route('product.index')->with('success_created', 'تم استيراد المنتجات بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    public function percent(Request $request)
    {
        $validate = $request->validate(
            [
                'category_id' => 'nullable|array',
                'category_id.*' => 'exists:categories,id',
                'percent' => 'required|numeric',
            ],
            [
                'category_id.*.exists' => 'يجب أن يكون الشركة من ضمن الشركات الموجودة',
                'percent.required' => 'يجب اختيار النسبة',
                'percent.numeric' => 'يجب أن تكون النسبة رقمية',
            ],
        );

        $query = Product::with('units');

        if (!empty($validate['category_id'])) {
            $query->whereIn('category_id', $validate['category_id']);
        }
        $products = $query->get();
        foreach ($products as $product) {
            foreach ($product->units as $unit) {
                $oldPrice = $unit->pivot->price;
                $newPrice = $oldPrice + ($oldPrice * $validate['percent']) / 100;
                $unit->pivot->update([
                    'price' => $newPrice,
                ]);
            }
        }

        return back()->with('success_updated', 'تم تغيير أسعار المنتجات بنجاح');
    }
}
