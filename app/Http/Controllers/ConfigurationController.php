<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pickup;
use App\Models\Configuration;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class ConfigurationController extends Controller
{
    public function index()
    {
        //Need to change Dynamic
        $shop = Auth::user()->name;
        $pickupAccounts = [];
        $configuration = [];

        $pickupAccounts = Pickup::where('shop', $shop)->where('status', '1')->get();
        if (!empty($pickupAccounts)) {
            $pickupAccounts = $pickupAccounts->toArray();
        }

        $configuration =  Configuration::where('shop', $shop)->first();
        if (!empty($configuration)) {
            $configuration = $configuration->toArray();
        }

        return view('configuration.configuration_view', compact('pickupAccounts', 'configuration'));
    }

    public function add(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                $country = $request->input('country');
                $currency = $request->input('currency');
                $accountType = $request->input('accountType');
                $soldtoAccount  = $request->input('soldtoAccount');
                $pickupAccounts = $request->input('pickupAccount');
                $labelShipping = $request->input('labelShipping');
                $productCode = $request->input('productCode');
                $prefix = $request->input('prefix');
                $clientId = $request->input('clientID');
                $clientSecret = $request->input('clientSecret');
                $labelTemplate = $request->input('labelTemplate');
                $labelFormat = $request->input('labelFormat');

                $access_token = "";
                $access_token_expire = "";

                $dhl_auth_response = $this->dhl_get_api_call(config('services.dhl.api_base_url') . '/rest/v1/OAuth/AccessToken', [
                    'clientId' => $clientId,
                    'password' => $clientSecret,
                    'returnFormat' => 'json'
                ]);

                if (isset($dhl_auth_response['accessTokenResponse']) && !empty($dhl_auth_response['accessTokenResponse'])) {
                    $access_token_response = $dhl_auth_response['accessTokenResponse'];
                    if (isset($access_token_response['token']) && !empty($access_token_response['token'])) {
                        $access_token = $access_token_response['token'];
                        $access_token_expire = $access_token_response['expires_in_seconds'];
                    }
                }

                if (!isset($access_token) || empty($access_token)) {
                    throw new Exception("Error in authentication.");
                }

                //Need to change Dynamic
                $shop = Auth::user()->name;

                if ($accountType != 'DHL eCommerce Asia') {
                    return response()->json(['status' => false, 'message' => "Not Allowed to Edit Account Type"]);
                }

                //Set to Pickup Account Default
                Pickup::where('shop', $shop)->update(['is_default' => '0']);
                $pickupAccount = Pickup::where('shop', $shop)->where('id', $pickupAccounts)->update(['is_default' => '1']);
                $is_updated = Configuration::where('shop', $shop)->first();

                if (!empty($is_updated)) {
                    $is_updated->country = $country;
                    $is_updated->currency = $currency;
                    $is_updated->account_type = $accountType;
                    $is_updated->soldto_account = $soldtoAccount;
                    $is_updated->pickup_account = $pickupAccounts;
                    $is_updated->enable_shipping = $labelShipping;
                    $is_updated->product_code = $productCode;
                    $is_updated->prefix = $prefix;
                    $is_updated->client_id = $clientId;
                    $is_updated->client_secret = $clientSecret;
                    $is_updated->access_token = $access_token;
                    $is_updated->access_token_expire = $access_token_expire;
                    $is_updated->label_template = $labelTemplate;
                    $is_updated->label_format = $labelFormat;
                    $is_updated->save();
                } else {
                    $configuration = new Configuration();
                    $configuration->shop = $shop;
                    $configuration->country = $country;
                    $configuration->currency = $currency;
                    $configuration->account_type = $accountType;
                    $configuration->soldto_account = $soldtoAccount;
                    $configuration->pickup_account = $pickupAccounts;
                    $configuration->enable_shipping = $labelShipping;
                    $configuration->product_code = $productCode;
                    $configuration->prefix = $prefix;
                    $configuration->client_id = $clientId;
                    $configuration->client_secret = $clientSecret;
                    $configuration->access_token = $access_token;
                    $configuration->access_token_expire = $access_token_expire;
                    $configuration->label_template = $labelTemplate;
                    $configuration->label_format = $labelFormat;
                    $configuration->save();
                }

                return response()->json(['status' => true, 'message' => "Configuration details have been saved successfully."]);
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function auth(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                $clientId = $request->input('clientID');
                $clientSecret = $request->input('clientSecret');
                $current_datetime = date('Y-m-d H:i:s');
                $dhl_auth_response = $this->dhl_get_api_call(config('services.dhl.api_base_url'). '/rest/v1/OAuth/AccessToken', [
                    'clientId' => $clientId,
                    'password' => $clientSecret,
                    'returnFormat' => 'json'
                ]);

                if (isset($dhl_auth_response['accessTokenResponse']) && !empty($dhl_auth_response['accessTokenResponse'])) {
                    $access_token_response = $dhl_auth_response['accessTokenResponse'];
                    if (isset($access_token_response['token']) && !empty($access_token_response['token'])) {
                        $access_token = $access_token_response['token'];
                        $access_token_expire = $access_token_response['expires_in_seconds'];
                    }
                }

                if (!isset($access_token) || empty($access_token)) {
                    throw new Exception("Error in authentication.");
                }

                //Need to change Dynamic
                $shop = Auth::user()->name;

                $is_updated = Configuration::where('shop', $shop)->first();
                if (!empty($is_updated)) {
                    $is_updated->client_id = $clientId;
                    $is_updated->client_secret = $clientSecret;
                    $is_updated->access_token = $access_token;
                    $is_updated->access_token_expire = $access_token_expire;
                    $is_updated->save();
                }
                return response()->json(['status' => false, 'message' => 'Authentication has been successful.']);
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function dhl_get_api_call($url, $params = [])
    {
        if (!empty($params) && is_array($params))
            $url = "$url?" . http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
}
