<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pickup;
use App\Models\Configuration;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Log;

class PickupController extends Controller
{
    public function index()
    {
        $shop = Auth::user()->name;
        $pickupAccount = Pickup::where('shop', $shop)->get();

        return view('pickup.list', compact('pickupAccount'));
    }

    public function show_pickup()
    {
        return view('pickup.add_pickup_view');
    }

    public function edit_pickup($id)
    {
        //Need to change Dymanic
        $shop = Auth::user()->name;

        $pickupAccount = Pickup::where('shop', $shop)->where('id', $id)->first();
        if (!empty($pickupAccount)) {
            $pickupAccount = $pickupAccount->toArray();
            return view('pickup.add_pickup_view', compact('pickupAccount'));
        } else {
            echo "
            <script>
            window.parent.location.href = 'https://admin.shopify.com/store/" . explode('.', request('shop'))[0] . "/apps/" . config('services.shopify-app.handle') . "/pickup-accounts';
            </script>
            ";
            exit();
        }
    }

    //List Pickup data
    public function list()
    {
        //Need to change Dymanic
        $shop = Auth::user()->name;
        $pickupAccount = Pickup::where('shop', $shop)->get();

        return DataTables::of($pickupAccount)->toJson();
    }

    // Store PickUp Data
    public function add(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                $number = $request->input('accountNumber');
                $company = $request->input('companyName');
                $name = $request->input('yourName');
                $address_line_1 = $request->input('addressLine1');
                $address_line_2 = $request->input('addressLine2');
                $address_line_3 = $request->input('addressLine3');
                $city = $request->input('city');
                $state = $request->input('state');
                $district = $request->input('district');
                $postcode = $request->input('postcode');
                $phone = $request->input('phone');
                $email = $request->input('email');
                $is_default = $request->input('isDefault');
                $status = $request->input('status');

                //Need to change Dymanic
                $shop = Auth::user()->name;

                if ($is_default == '1') {
                    Pickup::where('shop', $shop)->update(['is_default' => '0']);
                }

                $Pickup = new Pickup();
                $Pickup->shop = $shop;
                $Pickup->number = $number;
                $Pickup->company = $company;
                $Pickup->name = $name;
                $Pickup->address_line_1 = $address_line_1;
                $Pickup->address_line_2 = $address_line_2;
                $Pickup->address_line_3 = $address_line_3;
                $Pickup->city = $city;
                $Pickup->state = $state;
                $Pickup->district = $district;
                $Pickup->postcode = $postcode;
                $Pickup->phone = $phone;
                $Pickup->email = $email;
                $Pickup->is_default = $is_default;
                $Pickup->status = $status;
                $Pickup->save();

                return response()->json(['status' => true, 'message' => "Pickup account details have been added successfully."]);
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    //Update Pickup Account
    public function edit(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                $number = $request->input('accountNumber');
                $company = $request->input('companyName');
                $name = $request->input('yourName');
                $address_line_1 = $request->input('addressLine1');
                $address_line_2 = $request->input('addressLine2');
                $address_line_3 = $request->input('addressLine3');
                $city = $request->input('city');
                $state = $request->input('state');
                $district = $request->input('district');
                $postcode = $request->input('postcode');
                $phone = $request->input('phone');
                $email = $request->input('email');
                $id = $request->input('id');
                $is_default = $request->input('isDefault');
                $status = $request->input('status');

                //Need to change Dymanic
                $shop = Auth::user()->name;

                if ($is_default == 1) {
                    Pickup::where('shop', $shop)->update(['is_default' => '0']);
                } else {
                    $checkedDefaultStatus = Pickup::where('shop', $shop)->where('is_default', '1')->count();
                    if ($checkedDefaultStatus < 1) {
                        return response()->json(['status' => false, 'message' => "At Least One Account Must be Default."]);
                    }
                }

                $pickupAccount = Pickup::where('shop', $shop)->where('id', $id)->first();
                if (!empty($pickupAccount)) {
                    $pickupAccount->number = $number;
                    $pickupAccount->company = $company;
                    $pickupAccount->name = $name;
                    $pickupAccount->address_line_1 = $address_line_1;
                    $pickupAccount->address_line_2 = $address_line_2;
                    $pickupAccount->address_line_3 = $address_line_3;
                    $pickupAccount->city = $city;
                    $pickupAccount->state = $state;
                    $pickupAccount->district = $district;
                    $pickupAccount->postcode = $postcode;
                    $pickupAccount->phone = $phone;
                    $pickupAccount->email = $email;
                    $pickupAccount->is_default = $is_default;
                    $pickupAccount->status = $status;
                    $pickupAccount->save();

                    return response()->json(['status' => true, 'message' => "Pickup account details have been updated successfully."]);
                } else {
                    echo "
            <script>
            window.parent.location.href = 'https://admin.shopify.com/store/" . explode('.', request('shop'))[0] . "/apps/" . config('services.shopify-app.handle') . "/pickup-accounts';
            </script>
            ";
                    exit();
                }
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    //For Set Default Account
    public function default(Request $request)
    {
        $id = (isset($request->id) && $request->id != null) ? $request->id : '';
        $isDefault = (isset($request->isDefault) && $request->isDefault == "true") ? '1' : '0';

        //Need to change Dymanic
        $shop = Auth::user()->name;

        if ($isDefault == 1) {
            Configuration::where('shop', $shop)->update(['pickup_account' => $id]);
            Pickup::where('shop', $shop)->update(['is_default' => '0']);
            $pickupAccount = Pickup::where('shop', $shop)->where('id', $id)->update(['is_default' => $isDefault]);
        }

        return response()->json(['status' => true, 'message' => "Status has been updated successfully."]);
    }

    //For set Account Enable or Disable
    public function status(Request $request)
    {
        $id = (isset($request->id) && $request->id != null) ? $request->id : '';
        $status = (isset($request->status) && $request->status == "true") ? '1' : '0';

        //Need to change Dymanic
        $shop = Auth::user()->name;

        Pickup::where('shop', $shop)->where('id', $id)->update(['status' => $status]);
        return response()->json(['status' => true, 'message' => "Status has been updated successfully."]);
    }

    //For Delete Pickup Account
    public function delete(Request $request)
    {
        $id = (isset($request->id) && $request->id != null) ? $request->id : '';

        //Need to change Dymanic
        $shop = Auth::user()->name;

        $checkedDefaultStatus = Pickup::where('shop', $shop)->where('id', $id)->where('is_default', '1')->first();
        if (empty($checkedDefaultStatus)) {
            $pickup = Pickup::where('shop', $shop)->where('id', $id)->first();
            if ($pickup) {
                $pickup->delete();
                return response()->json(['status' => true, 'message' => "Record has been deleted successfully."]);
            } else {
                return response()->json(['status' => false, 'message' => "No Record Found."]);
            }
        } else {
            return response()->json(['status' => false, 'message' => "At Least One Account Must be Default."]);
        }
    }
}
