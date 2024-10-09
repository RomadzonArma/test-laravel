<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CustomerController extends Controller
{
    // public function validatePhone(Request $request)
    // {

    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'address' => 'required|string',
    //         'phone' => 'required|string',
    //     ]);

    //     // Ambil kunci API dari config
    //     $apiKey = config('services.abstract_api.key');
    //     $phoneNumber = $request->input('phone');

    //     // Buat permintaan ke AbstractAPI menggunakan Http Client Laravel
    //     $response = Http::get('https://phonevalidation.abstractapi.com/v1', [
    //         'api_key' => $apiKey,
    //         'phone' => $phoneNumber,
    //     ]);

    //     // Cek jika permintaan berhasil
    //     if ($response->successful()) {
    //         // $data = $response->json();

    //         // // Log respons dari API
    //         // Log::info('API Response:', $data);

    //         $customer = Customer::create([
    //             'name' => $request->name,
    //             'address' => $request->address,
    //             'phone' => $request->phone,
    //         ]);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Customer created successfully',
    //             'data' => $customer,
    //         ], 200);
    //     } else {
    //         // Log error jika gagal
    //         // Log::error('API Error:', [
    //         //     'response' => $response->body(),
    //         //     'status' => $response->status(),
    //         // ]);

    //         // Kembalikan respons JSON dengan error
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'The phone number is invalid',
    //         ], 400); // HTTP status code 500: Internal Server Error
    //     }
    // }
    public function store(Request $request)
{
    // Validate the input fields
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'phone' => 'required|string',
    ]);

    // Get the AbstractAPI key from config
    $apiKey = config('services.abstract_api.key');
    $phoneNumber = $request->input('phone');

    // Make a request to AbstractAPI to validate the phone number
    $response = Http::get('https://phonevalidation.abstractapi.com/v1/', [
        'api_key' => $apiKey,
        'phone' => $phoneNumber,
    ]);

    // Check if the request to AbstractAPI was successful
    if ($response->successful()) {
        $data = $response->json();

        // Check if the phone number is valid
        if (isset($data['valid']) && $data['valid'] === true) {
            // Phone number is valid, create the customer
            $customer = Customer::create([
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Customer created successfully',
                'data' => $customer,
            ], 201); // HTTP 201: Created
        } else {
            // Phone number is invalid, return an error
            return response()->json([
                'status' => 'error',
                'message' => 'The phone number is invalid',
            ], 400); // HTTP 400: Bad Request
        }
    } else {
        // Return a generic error response if API call fails
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to validate phone number.',
        ], 500); // HTTP 500: Internal Server Error
    }
}
public function update(Request $request, $id)
{

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'phone' => 'required|string',
    ]);


    $customer = Customer::findOrFail($id);

    $apiKey = config('services.abstract_api.key');
    $phoneNumber = $request->input('phone');

    $response = Http::get('https://phonevalidation.abstractapi.com/v1/', [
        'api_key' => $apiKey,
        'phone' => $phoneNumber,
    ]);

    // Check if the request to AbstractAPI was successful
    if ($response->successful()) {
        $data = $response->json();

        if (isset($data['valid']) && $data['valid'] === true) {
            $customer->update([
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer updated successfully',
                'data' => $customer,
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'The phone number is invalid',
            ], 400);
        }
    } else {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to validate phone number.',
        ], 500);
    }
}


}
