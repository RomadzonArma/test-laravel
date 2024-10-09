<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use App\Models\SalesOrder;
use App\Models\SalesTarget;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function getMonthlyTransactions(){
        $currentYear = Carbon::now()->year;
        $lastThreeYear = range($currentYear - 2, $currentYear );

        $item = [];
        foreach($lastThreeYear as $year){
            $monthlyData = [];

            for ($month = 1; $month <= 12; $month++) {
                // Ambil semua SalesOrder untuk bulan dan tahun yang ditentukan
                $salesOrders = SalesOrder::whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->with('salesOrderItems') // Ambil items yang terkait
                    ->get();

                // Hitung total penjualan untuk semua order dalam bulan tersebut
                $totalSales = $salesOrders->sum(function ($order) {
                    return $order->salesOrderItems->sum(function ($item) {
                        return $item->quantity * $item->selling_price;
                    });
                });

                // Format hasil penjualan
                $monthlyData[] = [
                    'x' => Carbon::create()->month($month)->format('M'),
                    'y' => number_format($totalSales, 2, '.', ''),
                ];
            }

            $item [] = [
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
                    ->where('customer_id', $customerId) // Filter berdasarkan customer
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
            'customer' => $customerId,
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
                ->where('sales_id', $salesId) // Filter berdasarkan sales
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
        'sales' => $salesId,
        'items' => $items,
    ]);
}




public function monthlyTargetsAndTransactions(Request $request)
    {
        // Ambil sales_id dari request (nullable)
        $salesId = $request->input('sales_id', null);
        $year = now()->year; // Ambil tahun saat ini

        // Inisialisasi data untuk respons
        $response = [
            'sales' => $salesId ? optional(Sale::find($salesId))->user->name : null, // Ambil nama sales jika ada
            'year' => $year,
            'items' => []
        ];

        // Hitung Target Bulanan
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

        // Hitung Revenue dan Income Bulanan
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

        // Tambahkan data ke respons
        $response['items'][] = ['name' => 'Target', 'data' => $targetData];
        $response['items'][] = ['name' => 'Revenue', 'data' => $revenueData];
        $response['items'][] = ['name' => 'Income', 'data' => $incomeData];

        return response()->json($response);
    }











    public function getAllMonthlyTargetsAndTransactions(Request $request)
    {
          // Ambil parameter dari request
    $salesId = $request->input('sales_id', null);
    $month = $request->input('month', now()->month); // Default bulan ini
    $isUnderperform = $request->input('is_underperform', null); // Nullable
    $year = now()->year; // Tahun saat ini


    // Konversi nilai is_underperform menjadi boolean jika ada
    if ($isUnderperform !== null) {
    $isUnderperform = filter_var($isUnderperform, FILTER_VALIDATE_BOOLEAN);
}

    // Inisialisasi data untuk respons
    $response = [
        'is_underperform' => $isUnderperform,
        'month' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
        'items' => []
    ];

    // Ambil data sales yang relevan (bisa tanpa filter sales_id atau dengan filter sales_id)
    $salesQuery = Sale::query();
    if ($salesId) {
        $salesQuery->where('id', $salesId);
    }

    // Ambil data sales
    $sales = $salesQuery->get();

    foreach ($sales as $sale) {
        // Hitung Target Bulanan
        $target = SalesTarget::whereMonth('active_date', $month)
            ->whereYear('active_date', $year)
            ->where('sales_id', $sale->id)
            ->sum('amount');

        // Hitung Revenue Bulanan
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

        // Filter berdasarkan is_underperform jika ada
        // if ($isUnderperform !== null) {
        //     $isUnderperformCheck = ($revenue < $target) ? true : false;
        //     if ($isUnderperformCheck != $isUnderperform) {
        //         continue; // Skip sales yang tidak sesuai filter
        //     }
        // }

        if ($isUnderperform !== null) {
            $isUnderperformCheck = ($revenue < $target);
            if ($isUnderperformCheck !== $isUnderperform) {
                continue; // Skip jika tidak sesuai dengan filter
            }
        }

        // Kalkulasi persentase pencapaian
        $percentage = $target > 0 ? number_format(($revenue / $target) * 100, 2) : null;

        // Format revenue dan target dengan abbreviation
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

    // Helper function untuk memformat angka menjadi singkatan
    private function abbreviateNumber($number) {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 2) . 'B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, 2) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 2) . 'K';
        }
        return $number;
    }


}
