<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Services\AddressService;

class AddressController extends Controller
{
    protected $service;

    public function __construct(AddressService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $addresses = $this->service->getAllForUser($request->user()->id);
        return response()->json($addresses);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'address_details' => 'required|string',
            'titel'  => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_default' => 'nullable|boolean',
            'area_id' => 'required|numeric'
        ]);

        $data['user_id'] = $request->user()->id;
        $address = $this->service->add($data);

        return response()->json($address, 201);
    }

    public function update(Request $request, $id)
    {
        $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $address->update($request->all());

        return response()->json($address->fresh());
    }


    public function destroy(Request $request, $id)
    {
        $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $result = $this->service->delete($address);

        return response()->json(['message' => $result['message']], $result['success'] ? 200 : 400);
    }

    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;

        $address = $this->service->getByIdForUser($id, $userId);

        if (!$address) {
            return response()->json(['message' => 'العنوان غير موجود أو لا يخص المستخدم'], 404);
        }

        return response()->json($address);
    }

}
