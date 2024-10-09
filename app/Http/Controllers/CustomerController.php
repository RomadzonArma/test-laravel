<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^(?:\+62|62|0)8[1-9][0-9]{7,11}$/'
            ],
        ]);

        $apiKey = config('services.abstract_api.key');
        $phoneNumber = $request->input('phone');

        $response = Http::get('https://phonevalidation.abstractapi.com/v1/', [
            'api_key' => $apiKey,
            'phone' => $phoneNumber,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['valid']) && $data['valid'] === true) {
                $customer = Customer::create([
                    'name' => $request->name,
                    'address' => $request->address,
                    'phone' => $request->phone,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer created successfully',
                    'data' => $customer,
                ], 201);
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
    public function update(Request $request, $id)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^(?:\+62|62|0)8[1-9][0-9]{7,11}$/'
            ],
        ]);


        $customer = Customer::findOrFail($id);

        $apiKey = config('services.abstract_api.key');
        $phoneNumber = $request->input('phone');

        $response = Http::get('https://phonevalidation.abstractapi.com/v1/', [
            'api_key' => $apiKey,
            'phone' => $phoneNumber,
        ]);

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
