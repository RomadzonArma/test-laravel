<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesTarget;
use Illuminate\Http\Request;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    //----------------SOAL NO 2------------------------
    public function getMonthlyTransactions()
    {
        $currentYear = Carbon::now()->year;
        $lastThreeYear = range($currentYear - 2, $currentYear);

        $item = [];
        foreach ($lastThreeYear as $year) {
            $monthlyData = [];

            for ($month = 1; $month <= 12; $month++) {
                // mengambil semua SalesOrder untuk bulan dan tahun yang ditentukan
                $salesOrders = SalesOrder::whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->with('salesOrderItems')
                    ->get();

                // mengitung total penjualan untuk semua order dalam bulan
                $totalSales = $salesOrders->sum(function ($order) {
                    return $order->salesOrderItems->sum(function ($item) {
                        return $item->quantity * $item->selling_price;
                    });
                });

                $monthlyData[] = [
                    'x' => Carbon::create()->month($month)->format('M'),
                    'y' => number_format($totalSales, 2, '.', ''),
                ];
            }

            $item[] = [
                'name' => $year,
                'data' => $monthlyData,
            ];
        }

        $response = [
            'customer' => null,
            'sales' => null,
            'items' => $item,
        ];
        return response()->json($response);
    }

    public function getMonthlyTransactionsByCustomer(Request $request, $customerId)
    {
        $currentYear = Carbon::now()->year;
        $lastThreeYears = range($currentYear - 2, $currentYear);

        $items = [];

        foreach ($lastThreeYears as $year) {
            $monthlyData = [];
            for ($month = 1; $month <= 12; $month++) {
                $salesOrders = SalesOrder::whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->where('customer_id', $customerId)
                    ->with('salesOrderItems')
                    ->get();

                $totalSales = $salesOrders->sum(function ($order) {
                    return $order->salesOrderItems->sum(function ($item) {
                        return $item->quantity * $item->selling_price;
                    });
                });

                $monthlyData[] = [
                    'x' => Carbon::create()->month($month)->format('M'),
                    'y' => number_format($totalSales, 2, '.', ''),
                ];
            }

            $items[] = [
                'name' => $year,
                'data' => $monthlyData,
            ];
            $customer = Customer::find($customerId);
        }

        return response()->json([
            'customer' => $customer ? $customer->name : null,
            'sales' => null,
            'items' => $items,
        ]);
    }


    public function getMonthlyTransactionsBySales(Request $request, $salesId)
    {
        $currentYear = Carbon::now()->year;
        $lastThreeYears = range($currentYear - 2, $currentYear);

        $items = [];

        foreach ($lastThreeYears as $year) {
            $monthlyData = [];
            for ($month = 1; $month <= 12; $month++) {
                $salesOrders = SalesOrder::whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->where('sales_id', $salesId)
                    ->with('salesOrderItems')
                    ->get();

                $totalSales = $salesOrders->sum(function ($order) {
                    return $order->salesOrderItems->sum(function ($item) {
                        return $item->quantity * $item->selling_price;
                    });
                });

                $monthlyData[] = [
                    'x' => Carbon::create()->month($month)->format('M'),
                    'y' => number_format($totalSales, 2, '.', ''),
                ];
            }

            $items[] = [
                'name' => $year,
                'data' => $monthlyData,
            ];
        }

        return response()->json([
            'customer' => null,
            'sales' =>  $salesId ? optional(Sale::find($salesId))->user->name : null,
            'items' => $items,
        ]);
    }




    //----------------------------S0AL NO 3-----------------------------
    public function monthlyTargetsAndTransactions(Request $request)
    {
        // mengambil sales_id dari request (nullable)
        $salesId = $request->input('sales_id', null);
        $year = now()->year;

        $response = [
            'sales' => $salesId ? optional(Sale::find($salesId))->user->name : null, // ambil nama sales jika ada
            'year' => $year,
            'items' => []
        ];

        // mengitung target bulanan
        $targetData = [];
        for ($month = 1; $month <= 12; $month++) {
            $target = SalesTarget::whereMonth('active_date', $month)
                ->whereYear('active_date', $year)
                ->when($salesId, function ($query) use ($salesId) {
                    return $query->where('sales_id', $salesId);
                })
                ->sum('amount');

            $targetData[] = [
                'x' => date('M', mktime(0, 0, 0, $month, 1)),
                'y' => number_format($target, 2)
            ];
        }

        // mengitung revenue & income Bulanan
        $revenueData = [];
        $incomeData = [];
        for ($month = 1; $month <= 12; $month++) {
            $salesOrders = SalesOrder::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->when($salesId, function ($query) use ($salesId) {
                    return $query->where('sales_id', $salesId);
                })
                ->with('salesOrderItems')
                ->get();

            $revenue = $salesOrders->sum(function ($order) {
                return $order->salesOrderItems->sum(function ($item) {
                    return $item->selling_price * $item->quantity;
                });
            });

            $income = $salesOrders->sum(function ($order) {
                return $order->salesOrderItems->sum(function ($item) {
                    return ($item->selling_price - $item->production_price) * $item->quantity;
                });
            });

            $revenueData[] = [
                'x' => date('M', mktime(0, 0, 0, $month, 1)),
                'y' => number_format($revenue, 2)
            ];
            $incomeData[] = [
                'x' => date('M', mktime(0, 0, 0, $month, 1)),
                'y' => number_format($income, 2)
            ];
        }

        $response['items'][] = ['name' => 'Target', 'data' => $targetData];
        $response['items'][] = ['name' => 'Revenue', 'data' => $revenueData];
        $response['items'][] = ['name' => 'Income', 'data' => $incomeData];

        return response()->json($response);
    }


    //----------------------SOAL 4---------------------------------
    public function getAllMonthlyTargetsAndTransactions(Request $request)
    {

        $salesId = $request->input('sales_id', null);
        $month = $request->input('month', now()->month);
        $isUnderperform = $request->input('is_underperform', null);
        $year = now()->year;

        // konversi nilai is_underperform menjadi boolean
        if ($isUnderperform !== null) {
            $isUnderperform = filter_var($isUnderperform, FILTER_VALIDATE_BOOLEAN);
        }

        $response = [
            'is_underperform' => $isUnderperform,
            'month' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'items' => []
        ];

        // mengambil data sales yg cocok
        $salesQuery = Sale::query();
        if ($salesId) {
            $salesQuery->where('id', $salesId);
        }
        $sales = $salesQuery->get();


        foreach ($sales as $sale) {
            // hitung target bulanan
            $target = SalesTarget::whereMonth('active_date', $month)
                ->whereYear('active_date', $year)
                ->where('sales_id', $sale->id)
                ->sum('amount');

            // hitung revenue bulanan
            $salesOrders = SalesOrder::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->where('sales_id', $sale->id)
                ->with('salesOrderItems')
                ->get();

            $revenue = $salesOrders->sum(function ($order) {
                return $order->salesOrderItems->sum(function ($item) {
                    return $item->selling_price * $item->quantity;
                });
            });

            if ($isUnderperform !== null) {
                $isUnderperformCheck = ($revenue < $target);
                if ($isUnderperformCheck !== $isUnderperform) {
                    continue;
                }
            }

            // Kalkulasi persentase pencapaian
            $percentage = $target > 0 ? number_format(($revenue / $target) * 100, 2) : null;

            $formattedRevenue = [
                'amount' => number_format($revenue, 2),
                'abbreviation' => $this->abbreviateNumber($revenue)
            ];

            $formattedTarget = [
                'amount' => number_format($target, 2),
                'abbreviation' => $this->abbreviateNumber($target)
            ];

            // Tambahkan data sales ke respons
            $response['items'][] = [
                'sales' => $sale->user->name,
                'revenue' => $formattedRevenue,
                'target' => $formattedTarget,
                'percentage' => $percentage
            ];
        }

        return response()->json($response);
    }

    private function abbreviateNumber($number)
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 2) . 'B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, 2) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 2) . 'K';
        }
        return $number;
    }

    //-----------------------------SOAL 7-----------------------------
    public function createSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            // create sales order
            $salesOrder = SalesOrder::create([
                'reference_no' => $request->reference_no,
                'sales_id' => $request->sales_id,
                'customer_id' => $request->customer_id,
            ]);

            // create sales order items
            foreach ($request->items as $item) {
                SalesOrderItem::create([
                    'quantity' => $item['quantity'],
                    'production_price' => $item['production_price'],
                    'selling_price' => $item['selling_price'],
                    'product_id' => $item['product_id'],
                    'order_id' => $salesOrder->id,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Sales Order created successfully', 'data' => $salesOrder], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales order', 'error' => $e->getMessage()], 500);
        }
    }

    public function getById($reference_no)
    {
        $data  = SalesOrder::where('reference_no', $reference_no)->first();
        return response()->json(['messgae' => 'sucesss', 'data' => $data], 200);
    }
}
