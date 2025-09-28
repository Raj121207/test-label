<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pickup;
use App\Models\Configuration;
use App\Models\Label;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF;
use Illuminate\Support\Facades\Http;
use ZipArchive;
class ShippinglabelController extends Controller
{
    protected $shop;
    protected $configuration;
    protected $pickupAccounts;
    protected $shop_dir;
    protected $requested_data_dir;
    protected $label_json_dir;
    protected $labels_dir;
    protected $api_response_dir;
    protected $zip_label_dir;

    public function __construct()
    {
        // Prevent execution during console commands (e.g., route:list)
        if (app()->runningInConsole()) {
            return;
        }

        // Only proceed if 'shop' is present in the request
        if (request()->has('shop')) {
            $this->shop = User::where('name', request('shop'))->first();

            if ($this->shop) {
                $this->configuration = Configuration::where('shop', $this->shop->name)->first();
                if (!empty($this->configuration)) {
                    $this->configuration = $this->configuration->toArray();
                }

                $this->pickupAccounts = Pickup::where('shop', $this->shop->name)
                                            ->where('status', '1')
                                            ->get();

                if (!empty($this->pickupAccounts)) {
                    $this->pickupAccounts = $this->pickupAccounts->toArray();
                }

                $this->shop_dir           = $this->shop->id;
                $this->requested_data_dir = $this->shop_dir . "/labels/requested_data";
                $this->label_json_dir     = $this->shop_dir . "/labels/label_json";
                $this->labels_dir         = $this->shop_dir . "/labels/labels";
                $this->api_response_dir   = $this->shop_dir . "/labels/api_response";
                $this->zip_label_dir      = $this->shop_dir . "/labels/zip_labels";
            }
        }
    }

    public function getOrderRowData($id)
    {
        $storeName      = $this->shop->name;
        $accessToken    = $this->shop->password; //"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";
        //dd($storeName ,$accessToken);
        $apiUrl         = "https://" . $storeName . "/admin/api/" . config('shopify-app.api_version') . "/orders/" . $id . ".json";
        $headers = [
            "Content-Type: application/json",
            "X-Shopify-Access-Token: $accessToken"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);        
        return json_decode($response, true);
    }

    public function getOrderDetails($id)
    {
        $shopName = $this->shop->name;
        $accessToken = $this->shop->password;
        $orderID = $id;

        $query = '
    query getOrder($id: ID!) {
        order(id: $id) {
            id
            name
            currencyCode
            createdAt
            clientIp
            currentTotalWeight
            lineItems(first: 10) {
                edges {
                    node {
                        id
                        name
                        title                                                
                        variant {
                            id
                            weight
                            weightUnit
                        }
                    }
                }
            }
            billingAddress {
                company
                name
                address1
                address2
                city
                province
                country
                zip
                countryCodeV2
            }
            shippingAddress {
                company
                name
                address1
                address2
                city
                province
                country
                zip
                countryCodeV2
                phone
            }
            customer {
                id
                firstName
                lastName
                displayName
                email
                phone
                defaultAddress {
                    company
                    name
                    address1
                    address2
                    city
                    province
                    country
                    zip
                    countryCodeV2
                    phone
                }
            }
        }
    }
    ';

        $variables = [
            'id' => "gid://shopify/Order/{$orderID}"
        ];

        $data = [
            'query' => $query,
            'variables' => $variables,
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://$shopName/admin/api/" . config('shopify-app.api_version') . "/graphql.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Shopify-Access-Token: ' . $accessToken,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['data']['order'])) {
            return $result;
        } elseif (isset($result['errors'][0]['message'])) {
            Log::info('Error: ' . $result['errors'][0]['message']);
        } else {
            Log::info('Unknown error occurred.');
        }
    }

    public function index()
    {
        $labels = Label::where('shop', $this->shop->name)->orderBy('id', 'DESC')->get();
        return view('shippingLabel.list', compact('labels'));
    }

    public function create()
    {
        $labels = Label::where('shop', $this->shop->name)->where('order_id', request('id'))->first();
        if ($labels) {
            echo "
                <script>
                    window.parent.location.href = 'https://admin.shopify.com/store/" . explode('.', $this->shop->name)[0] . "/apps/" . config('services.shopify-app.handle') . "/shipping-label/view/" . request('id') . "';
                </script>
            ";
            exit();
        } else {
            $pickup_accounts = $this->pickupAccounts;
            $configuration = $this->configuration;
            $getOrderRowData = $this->getOrderRowData(request('id'));
            if (!isset($getOrderRowData['order'])) {
                \Log::error('Shopify order API response missing "order" key', ['response' => $getOrderRowData]);
                return back()->with('error', 'Could not fetch order details from Shopify. Please check your app permissions and try again.');
            }
            $orderData = $getOrderRowData['order'];
            $payment_method = empty($orderData['payment_gateway_names']) ? ['manual'] : $orderData['payment_gateway_names'];
            $total_price = $orderData['total_price'] ?? 0;
            $order = $this->getOrderDetails(request('id'));
            $order_edge = $order['data']['order'];
            $order_id = explode('/', $order_edge['id']);
            $weight = $this->calculateVariantWeights($order_edge['lineItems']['edges']);
            $order = [
                "id" => end($order_id),
                "name" => $order_edge['name'],
                "currencyCode" => $order_edge['currencyCode'],
                "createdAt" => $order_edge['createdAt'],
                "clientIp" => $order_edge['clientIp'],
                "currentTotalWeight" => $order_edge['currentTotalWeight'],
                "billingAddress" => $order_edge['billingAddress'],
                "shippingAddress" => $order_edge['shippingAddress'],
                "customer" => $order_edge['customer'],
                "variantWeight" => $weight
            ];

            $product_info = [];
            if (isset($order_edge['lineItems']['edges'])) {
                $products = $order_edge['lineItems']['edges'];
                foreach ($products as $product) {
                    $product_id = explode('/', $product['node']['id']);
                    $product_info[] = [
                        'id' => end($product_id),
                        'name' => $product['node']['name'],
                        'title' => $product['node']['title'],
                        'variant' => $product['node']['variant']
                    ];
                }
            }
            $order['products'] = isset($product_info) && !empty($product_info) ? $product_info : [];
            return view('shippingLabel.create', compact('pickup_accounts', 'configuration', 'order', 'payment_method', 'total_price'));
        }
    }

    public function store(Request $request)
    {
        $shop = $this->shop;
        try {
            $current_datetime = date('Y-m-d H:i:s');
            $pickup_account_id = $request->pickupAccount;
            $handover_method = $request->handoverMethod;
            $handover_method = (int) $handover_method;
            $pickup_date = $request->pickupDate;
            $pickup_date = ($handover_method == 1) ? null : $this->format_pickup_date(date('Y-m-d H:i:s', strtotime($pickup_date)));

            $consolidated_label = "Y";
            $shipment_id = $request->shipmentID;
            #$shipment_id = 'MYMSW' . time();
            $package_description = $request->packageDescription;
            $product_code = $request->productCode;
            $cash_on_delivery = $request->cashOnDelivery;
            $paper_proof_delivery_option = $request->paperProofDeliveryOption;
            $open_box = $request->openBox;
            $shipment_value_protection = $request->shipmentValueProtection;
            $currency = $request->currency;
            $remarks = $request->remarks;
            $shipment_weight = $request->shipmentWeight;
            $multi_pieces_shipment = $request->multiPiecesShipment;

            if ($multi_pieces_shipment == 'true') {
                $delivery_option = $request->deliveryOption;
                $shipment_pieces = $request->shipmentPieces;
                $shipment_pieces = json_decode($shipment_pieces, true);
            }

            $order = $request->order;
            $return_mode = $request->returnMode;
            $return_address = $request->returnAddress;

            if ($handover_method == 2) {
                if (empty($pickup_date)) {
                    throw new Exception("Please enter pickup date.");
                }
            }

            if (empty($pickup_account_id)) {
                throw new Exception("Please select pickup account.");
            }

            if (empty($package_description)) {
                throw new Exception("Please enter package description.");
            }

            if (empty($return_mode)) {
                throw new Exception("Please enter return mode.");
            }

            if (empty($shipment_weight)) {
                throw new Exception("Please enter Shipment Weight.");
            }

            $configuration = $this->configuration;
            $pickup_account = Pickup::where('shop', $this->shop->name)->where('id', $pickup_account_id)->where('status', '1')->first();
            if (!empty($pickup_account)) {
                $pickup_account = $pickup_account->toArray();
            }

            if (!isset($pickup_account) && empty($pickup_account)) {
                throw new Exception("Couldn't find pickup account, Please select it from Configuration and Try again.!!");
            }
            $access_token = $configuration['access_token'];
            $dhl_auth_response = $this->dhl_get_api_call(config('services.dhl.api_base_url') . '/rest/v1/OAuth/AccessToken', [
                'clientId' => $configuration['client_id'],
                'password' => $configuration['client_secret'],
                'returnFormat' => 'json'
            ]);
            if (isset($dhl_auth_response['accessTokenResponse']) && !empty($dhl_auth_response['accessTokenResponse'])) {
                $access_token_response = $dhl_auth_response['accessTokenResponse'];
                if (isset($access_token_response['token']) && !empty($access_token_response['token'])) {
                    $access_token = $access_token_response['token'];
                }
            }

            $hdr = [
                'messageType' => 'LABEL',
                'messageDateTime' => '2022-09-02T13:01:13+05:30',
                'accessToken' => $access_token,
                'messageVersion' => '1.4',
                'messageLanguage' => 'en',
                'messageSource' => 'Shopify'
            ];

            $bd = [
                'inlineLabelReturn' => 'Y',
                'customerAccountId' => null,
                'pickupAccountId' => isset($pickup_account['number']) ? $pickup_account['number'] : '',
                'soldToAccountId' => isset($configuration['soldto_account']) ? $configuration['soldto_account'] : '',
                'handoverMethod' => isset($handover_method) ? $handover_method : 1,
                'pickupDateTime' => $pickup_date,
                'consolidatedLabelRequired' => $consolidated_label,
                'pickupAddress' => [
                    'companyName' => $pickup_account['company'],
                    'name' => $pickup_account['name'],
                    'address1' => $pickup_account['address_line_1'],
                    'address2' => $pickup_account['address_line_2'],
                    'city' => $pickup_account['city'],
                    'state' => isset($pickup_account['state']) && !empty($pickup_account['state']) ? $pickup_account['state'] : null,
                    'district' => isset($pickup_account['district']) && !empty($pickup_account['district']) ? $pickup_account['district'] : null,
                    'country' => $configuration['country'],
                    'postCode' => $pickup_account['postcode'],
                    'phone' => $pickup_account['phone'],
                    'email' => $pickup_account['email']
                ]
            ];

            $shipment_item = [
                'consigneeAddress' => [
                    'companyName' => (isset($order['shippingAddress']['company'])
                        ? $order['shippingAddress']['company'] : (isset($order['billingAddress']['company'])
                            ? $order['billingAddress']['company'] : null)),
                    'name' => (isset($order['shippingAddress']['name'])
                        ? $order['shippingAddress']['name'] : (isset($order['billingAddress']['name'])
                            ? $order['billingAddress']['name'] : null)),
                    'address1' => (isset($order['shippingAddress']['address1'])
                        ? $order['shippingAddress']['address1'] : (isset($order['billingAddress']['address1'])
                            ? $order['billingAddress']['address1'] : null)),
                    'address2' => (isset($order['shippingAddress']['address2'])
                        ? $order['shippingAddress']['address2'] : (isset($order['billingAddress']['address2'])
                            ? $order['billingAddress']['address2'] : null)),
                    'address3' => (isset($order['shippingAddress']['address3'])
                        ? $order['shippingAddress']['address3'] : (isset($order['billingAddress']['address3'])
                            ? $order['billingAddress']['address3'] : null)),
                    'city' => (isset($order['shippingAddress']['city'])
                        ? $order['shippingAddress']['city'] : (isset($order['billingAddress']['city'])
                            ? $order['billingAddress']['city'] : null)),
                    'state' => (isset($order['shippingAddress']['province'])
                        ? $order['shippingAddress']['province'] : (isset($order['billingAddress']['province'])
                            ? $order['billingAddress']['province'] : null)),
                    'district' => null,
                    'country' => (isset($order['shippingAddress']['countryCodeV2'])
                        ? ($order['shippingAddress']['countryCodeV2'] == 'IN' ? 'MY' : $order['shippingAddress']['countryCodeV2']) : (isset($order['billingAddress']['countryCodeV2'])
                            ? ($order['billingAddress']['countryCodeV2'] == 'IN' ? 'MY' : $order['billingAddress']['countryCodeV2']) : null)),
                    'postCode' => (isset($order['shippingAddress']['zip'])
                        ? $order['shippingAddress']['zip'] : (isset($order['billingAddress']['zip'])
                            ? $order['billingAddress']['zip'] : null)),
                    'phone' => isset($order['shippingAddress']['phone']) ? $order['shippingAddress']['phone'] : $order['customer']['phone'],
                    'email' => isset($order['customer']['email']) ? $order['customer']['email'] : null,
                    'idNumber' => null,
                    'idType' => null,
                ],
                'shipmentID' => $shipment_id,
                'returnMode' => $return_mode,
                'packageDesc' => substr($package_description, 0, 50),
                'totalWeight' => (int) $shipment_weight,
                'totalWeightUOM' => 'G',
                'productCode' => $product_code,
                'codValue' => !empty($cash_on_delivery) ? floatval($cash_on_delivery) : null,
                'insuranceValue' => !empty($shipment_value_protection) ? floatval($shipment_value_protection) : null,
                'currency' => $currency,
                'remarks' => isset($remarks) && !empty($remarks) ? $remarks : null,
                'isMult' => $multi_pieces_shipment ? "true" : "false"
            ];

            if ($paper_proof_delivery_option == '1' && $open_box == '1') {
                $shipment_item['valueAddedServices'] = [
                    'valueAddedService' => [['vasCode' => 'PPOD'], ['vasCode' => 'OBOX']]
                ];
            } else {
                if ($paper_proof_delivery_option == '1') {
                    $shipment_item['valueAddedServices'] = [
                        'valueAddedService' => [['vasCode' => 'PPOD']]
                    ];
                }

                if ($open_box == '1') {
                    $shipment_item['valueAddedServices'] = [
                        'valueAddedService' => [['vasCode' => 'OBOX']]
                    ];
                }
            }

            if ($return_mode == "02") {
                $shipment_item['returnAddress'] = [
                    'companyName' => $pickup_account['company'],
                    'name' => $pickup_account['name'],
                    'address1' => $pickup_account['address_line_1'],
                    'address2' => $pickup_account['address_line_2'],
                    'address3' => $pickup_account['address_line_3'],
                    'city' => $pickup_account['city'],
                    'state' => isset($pickup_account['state']) && !empty($pickup_account['state']) ? $pickup_account['state'] : null,
                    'district' => isset($pickup_account['district']) && !empty($pickup_account['district']) ? $pickup_account['district'] : null,
                    'country' => $configuration['country'],
                    'postCode' => $pickup_account['postcode'],
                    'phone' => $pickup_account['phone'],
                    'email' => $pickup_account['email']
                ];
            } elseif ($return_mode == "03") {
                $shipment_item['returnAddress'] = [
                    'companyName' => $return_address['companyName'],
                    'name' => $return_address['name'],
                    'address1' => $return_address['address1'],
                    'address2' => $return_address['address2'],
                    'address3' => $return_address['address3'],
                    'city' => $return_address['city'],
                    'state' => isset($return_address['state']) && !empty($return_address['state']) ? $return_address['state'] : null,
                    'district' => isset($return_address['district']) && !empty($return_address['district']) ? $return_address['district'] : null,
                    'country' => $return_address['country'],
                    'postCode' => $return_address['postcode'],
                    'phone' => $return_address['phone'],
                    'email' => $return_address['email']
                ];
            }

            if (isset($delivery_option) && !empty($delivery_option)) {
                $shipment_item['deliveryOption'] = $delivery_option;
            }

            if (isset($shipment_pieces) && !empty($shipment_pieces)) {
                $shipment_item['shipmentPieces'] = $shipment_pieces;
            }

            $shipment_items = [$shipment_item];
            $bd['shipmentItems'] = $shipment_items;
            $label = [
                'pageSize' => '400x600',
                'layout' => isset($configuration['label_template']) && $configuration['label_template'] == '1' ? '1x1' : '4x1',
                'format' => isset($configuration['label_format']) && !empty($configuration['label_format']) ? $configuration['label_format'] : 'PNG',
            ];
            $bd['label'] = $label;
            $request_data = [
                'labelRequest' => [
                    'hdr' => $hdr,
                    'bd' => $bd
                ]
            ];

            Storage::disk('local')->put($this->requested_data_dir . "/" . $shipment_id . ".json", json_encode($request_data));
            $dhl_label_response = $this->dhl_post_api_call(config('services.dhl.api_base_url') . '/rest/v2/Label', $request_data);
            
            Storage::disk('local')->put($this->api_response_dir . "/" . $shipment_id . ".json", json_encode($dhl_label_response));

            if (isset($dhl_label_response['labelResponse']['bd']['labels'][0]['responseStatus'])) {
                $labels = $dhl_label_response['labelResponse']['bd']['labels'];
                if (!empty($labels) && isset($labels[0]) && isset($labels[0]['responseStatus'])) {
                    $label = $labels[0];
                    $response_status = $label['responseStatus'];

                    if (isset($response_status['message']) && $response_status['message'] == 'SUCCESS') {

                        $shipment_id = $label['shipmentID'];
                        $delivery_confirmation_no = isset($label['deliveryConfirmationNo']) ? $label['deliveryConfirmationNo'] : '';
                        $content = $label['content'];
                        $ppod_content = $paper_proof_delivery_option == '1' && isset($label['ppodLabel']) ? $label['ppodLabel'] : '';

                        if ($multi_pieces_shipment == 'true' && isset($label['pieces']) && !empty($label['pieces'])) {
                            $pieces = $label['pieces'];
                        }

                        $label_format = isset($configuration['label_format']) && !empty($configuration['label_format']) ? $configuration['label_format'] : 'PNG';
                        $label_format_ext = strtolower($label_format);

                        if ($multi_pieces_shipment == 'true' && isset($pieces)) {
                            $generated_labels = [];

                            foreach ($pieces as $piece) {
                                $image = $shipment_id . "-" . $piece['shipmentPieceID'] . "." . $label_format_ext;

                                if ($label_format == "PNG" || $label_format == "ZPL") {
                                    Storage::disk('local')->put($this->labels_dir . '/' . $image, base64_decode($piece['content']));
                                } elseif ($label_format == "PDF") {
                                    $pdf_decoded = base64_decode($piece['content']);
                                    $pdf_name = Storage::disk('local')->path($this->labels_dir . '/' . $image);
                                    $pdf = fopen($pdf_name, 'w');
                                    fwrite($pdf, $pdf_decoded);
                                    fclose($pdf);
                                }

                                $generated_labels[] = [
                                    "label" => $image,
                                    "delivery_confirmation_no" => $piece['deliveryConfirmationNo'],
                                    "extension" => $label_format_ext
                                ];
                            }

                            if ($paper_proof_delivery_option == '1' && isset($pieces[0]['ppodLabel'])) {
                                $single_piece = $pieces[0];
                                $ppod_image = $shipment_id . "-PPOD." . $label_format_ext;
                                $ppod_content = $single_piece['ppodLabel'];

                                if ($label_format == "PNG" || $label_format == "ZPL") {
                                    Storage::disk('local')->put($this->labels_dir . '/' . $ppod_image, base64_decode($ppod_content));
                                } elseif ($label_format == "PDF") {
                                    $ppod_pdf_decoded = base64_decode($ppod_content);
                                    $ppod_pdf_name = Storage::disk('local')->path($this->labels_dir . '/' . $ppod_image);
                                    $ppod_pdf = fopen($ppod_pdf_name, 'w');
                                    fwrite($ppod_pdf, $ppod_pdf_decoded);
                                    fclose($ppod_pdf);
                                }
                                $generated_labels[0]['ppod'] = $ppod_image;
                            }

                        } else {
                            $image = $shipment_id . "." . $label_format_ext;
                            $ppod_image = $shipment_id . "-PPOD." . $label_format_ext;

                            if ($label_format == "PNG" || $label_format == "ZPL") {
                                Storage::disk('local')->put($this->labels_dir . '/' . $image, base64_decode($content));
                                if (!empty($ppod_content)) {
                                    Storage::disk('local')->put($this->labels_dir . '/' . $ppod_image, base64_decode($ppod_content));
                                }
                            } elseif ($label_format == "PDF") {
                                $pdf_decoded = base64_decode($content);
                                $pdf_name = Storage::disk('local')->path($this->labels_dir . '/' . $image);
                                $pdf = fopen($pdf_name, 'w');
                                fwrite($pdf, $pdf_decoded);
                                fclose($pdf);

                                if (!empty($ppod_content)) {
                                    $ppod_pdf_decoded = base64_decode($ppod_content);
                                    $ppod_pdf_name = Storage::disk('local')->path($this->labels_dir . '/' . $ppod_image);
                                    $ppod_pdf = fopen($ppod_pdf_name, 'w');
                                    fwrite($ppod_pdf, $ppod_pdf_decoded);
                                    fclose($ppod_pdf);
                                }
                            }

                            $generated_labels = [
                                [
                                    "label" => $image,
                                    "ppod" => $paper_proof_delivery_option == '1' ? $ppod_image : '',
                                    "delivery_confirmation_no" => $delivery_confirmation_no,
                                    "extension" => $label_format_ext
                                ]
                            ];
                        }

                        Storage::disk('local')->put($this->label_json_dir . "/" . $shipment_id . ".json", json_encode($request_data));

                        $is_updated = Label::where([
                            'shop' => $this->shop->name,
                            'order_id' => $order['id']
                        ])->update([
                            'shipment_id' => $shipment_id,
                            'delivery_confirmation_no' => $delivery_confirmation_no,
                            'content' => "",
                            'image' => $image,
                            'generated_labels' => !empty($generated_labels) ? json_encode($generated_labels) : '',
                            'updated_at' => $current_datetime
                        ]);

                        if (empty($is_updated)) {
                            $newLabel = new Label;
                            $newLabel->shop = $this->shop->name;
                            $newLabel->order_id = $order['id'];
                            $newLabel->order_name = $order['name'];
                            $newLabel->shipment_id = $shipment_id;
                            $newLabel->delivery_confirmation_no = $delivery_confirmation_no;
                            $newLabel->content = $content;
                            $newLabel->image = $image;
                            $newLabel->generated_labels = !empty($generated_labels) ? json_encode($generated_labels) : '';
                            $newLabel->created_at = $current_datetime;
                            $newLabel->updated_at = $current_datetime;
                            $newLabel->save();
                        }
                        
                        $this->fulfillOrderWithTracking($order['id']);

                        return response()->json(['status' => true, 'message' => "Label print successfully."]);
                    } else {
                        throw new Exception($response_status['messageDetails'][0]['messageDetail']);
                    }
                }
            } else {
                if (isset($dhl_label_response['labelResponse']['bd']['responseStatus'])) {
                    $bd_response = $dhl_label_response['labelResponse']['bd']['responseStatus'];
                    if ($bd_response['code'] != '200' || $bd_response['message'] != 'SUCCESS') {
                        return response()->json(['status' => false, 'message' => $bd_response['messageDetails'][0]['messageDetail']]);
                    }
                }
            }
        } catch (Exception $ex) {
            return response()->json(['status' => false, 'message' => $ex->getMessage()]);
        }
    }

    private function fulfillOrderWithTracking($orderId)
    {
        try {
            $shopDomain = $this->shop->name;
            $accessToken = $this->shop->password;

            // ? Step 1: Get fulfillment orders
            $query = <<<GRAPHQL
            query {
                order(id: "gid://shopify/Order/{$orderId}") {
                    id
                    name
                    fulfillmentOrders(first: 10) {
                        edges {
                            node {
                                id
                                status
                                assignedLocation {
                                    location {
                                        id
                                        name
                                    }
                                }
                            }
                        }
                    }
                }
            }
            GRAPHQL;

            $response = $this->executeGraphQL($shopDomain, $accessToken, $query);
            $fulfillmentOrders = $response['data']['order']['fulfillmentOrders']['edges'] ?? [];

            if (empty($fulfillmentOrders)) {
                // ?? Fulfillment orders not found � cannot proceed
                \Log::warning("No fulfillment orders found for Order ID: {$orderId}. Order may not be paid or is digital.");
                return null;
            }

            // ? Use the first available fulfillment order
            $fulfillmentOrderId = $fulfillmentOrders[0]['node']['id'];

            // ? Step 2: Create fulfillment with tracking
            $mutation = <<<GRAPHQL
            mutation fulfillmentCreateV2(\$fulfillment: FulfillmentV2Input!) {
                fulfillmentCreateV2(fulfillment: \$fulfillment) {
                    fulfillment {
                        id
                        status
                        trackingInfo {
                            company
                            number
                            url
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GRAPHQL;

            $variables = [
                'fulfillment' => [
                    'fulfillmentOrderId' => $fulfillmentOrderId,
                    'trackingInfo' => [
                        [
                            "company" => "DHL eCommerce Asia", // Must be a Shopify-supported carrier name
                            "number"  => "1234567890",
                            "url"     => "https://webtrack.dhl.com/?trackingNumber=1234567890"
                        ]
                    ],
                    'notifyCustomer' => true
                ]
            ];

            $fulfillResponse = $this->executeGraphQL($shopDomain, $accessToken, $mutation, $variables);

            // ? Check for userErrors
            if (!empty($fulfillResponse['data']['fulfillmentCreateV2']['userErrors'])) {
                \Log::error("Fulfillment failed for Order {$orderId}: " . json_encode($fulfillResponse['data']['fulfillmentCreateV2']['userErrors']));
                return null;
            }

            return $fulfillResponse;

        } catch (\Exception $e) {
            \Log::error("Exception in fulfillOrderWithTracking: " . $e->getMessage());
            return null;
        }
    }

    public function updateTrackingInfo($orderId, $shipment_id, $company = 'DHL eCommerce APAC', $notifyCustomer = true)
    {
       $fulfillmentId =  $this->getFulfillmentId($orderId);
        $trackingUrl = "https://ecommerceportal.dhl.com/track/pages/customer/trackItNowPublic.xhtml?ref={$shipment_id}";
        //dd($fulfillmentId);
        $mutation = <<<GRAPHQL
        mutation FulfillmentTrackingInfoUpdate(\$fulfillmentId: ID!, \$trackingInfoInput: FulfillmentTrackingInput!, \$notifyCustomer: Boolean) {
          fulfillmentTrackingInfoUpdate(
            fulfillmentId: \$fulfillmentId, 
            trackingInfoInput: \$trackingInfoInput, 
            notifyCustomer: \$notifyCustomer
          ) {
            fulfillment {
              id
              status
              trackingInfo {
                company
                number
                url
              }
            }
            userErrors {
              field
              message
            }
          }
        }
        GRAPHQL;
    
        $variables = [
            'fulfillmentId' => $fulfillmentId,
            'notifyCustomer' => $notifyCustomer,
            'trackingInfoInput' => [
                'company' => $company,
                'number' => $shipment_id,
                'url' => $trackingUrl
            ]
        ];
    
        $response = $this->callShopifyGraphQLAPI($mutation, $variables);
        if (!empty($response['data']['fulfillmentTrackingInfoUpdate']['userErrors'])) {
            throw new \Exception("Shopify Error: " . json_encode($response['data']['fulfillmentTrackingInfoUpdate']['userErrors']));
        }
    
        return $response['data']['fulfillmentTrackingInfoUpdate']['fulfillment'] ?? null;
    }

    public function getFulfillmentId($orderId)
    {
        $query = <<<GRAPHQL
        query FulfillmentList(\$orderId: ID!) {
          order(id: \$orderId) {
            fulfillments(first: 10) {
              id
              status
              createdAt
              fulfillmentLineItems(first: 10) {
                edges {
                  node {
                    id
                    quantity
                    lineItem {
                      title
                    }
                  }
                }
              }
              trackingInfo(first: 10) {
                company
                number
                url
              }
            }
          }
        }
        GRAPHQL;
        $variables = [
            'orderId' => "gid://shopify/Order/{$orderId}"
        ];
        $response = $this->callShopifyGraphQLAPI($query, $variables);
        return $response['data']['order']['fulfillments'][0]['id'] ?? null;
    }

    /**
     * Helper function to execute GraphQL requests
     */
    private function callShopifyGraphQLAPI($query, $variables = [])
    {
        $storeName      = $this->shop->name;
        $accessToken    = $this->shop->password;
        
        $payload = json_encode([
            'query' => $query,
            'variables' => $variables
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$storeName}/admin/api/2024-07/graphql.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Shopify-Access-Token: {$accessToken}",
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            dd('cURL Error:', curl_error($ch));
        }
        curl_close($ch);

        return json_decode($result, true);
    }

    public function view($order_id)
    {
        $shop           = $this->shop;
        $label_data     = Label::where('shop', $shop->name)->where('order_id', $order_id)->first();
        if (!empty($label_data)) {
            $label_data = $label_data->toArray();
        }

        $generated_labels = [];
        $shipment_id = [];

        if (!empty($label_data)) {
            $generated_labels = !empty($label_data['generated_labels']) ? json_decode($label_data['generated_labels'], true) : [];

            foreach ($generated_labels as $key => $generated_label) {
                $generated_labels[$key]['label']    =  asset('storage/app/' . $this->labels_dir . "/" . $generated_label['label']);
                $generated_labels[$key]['ppod']     = !empty($generated_label['ppod']) ? asset('storage/app/' . $this->labels_dir . "/" . $generated_label['ppod']) : '';
            }
            $shipment_id = $label_data['shipment_id'];
        }

        return view('shippingLabel.view', compact('generated_labels', 'order_id', 'shipment_id'));
    }

    public function edit($order_id)
    {
        //Need to change Dynamic
        $shop               = $this->shop;
        $labels             = Label::where('shop', $shop->name)->where('order_id', $order_id)->first()->toArray();
        $pickupAccounts     = $this->pickupAccounts;
        $configuration      = $this->configuration;
        dd($this->getOrderRowData($order_id));
        $getOrderRowData    = $this->getOrderRowData($order_id)['order'];
        $payment_method     = $getOrderRowData['payment_gateway_names'];
        $total_price        = $getOrderRowData['total_price'];
        $order              = $this->getOrderDetails($order_id);
        $order_edge         = $order['data']['order'];
        $order_id           = explode('/', $order_edge['id']);
        $order              = [
            "id"                    => end($order_id),
            "name"                  => $order_edge['name'],
            "currencyCode"          => $order_edge['currencyCode'],
            "createdAt"             => $order_edge['createdAt'],
            "clientIp"              => $order_edge['clientIp'],
            "currentTotalWeight"    => $order_edge['currentTotalWeight'],
            "billingAddress"        => $order_edge['billingAddress'],
            "shippingAddress"       => $order_edge['shippingAddress'],
            "customer"              => $order_edge['customer']
        ];

        $product_info = [];
        if (isset($order_edge['lineItems']['edges'])) {
            $products = $order_edge['lineItems']['edges'];
            foreach ($products as $product) {
                $product_id     = explode('/', $product['node']['id']);
                $product_info[] = [
                    'id'        => end($product_id),
                    'name'      => $product['node']['name'],
                    'title'     => $product['node']['title']
                ];
            }
        }


        $order['products'] = isset($product_info) && !empty($product_info) ? $product_info : [];
        $label_json_data =  Storage::disk('local')->get($this->label_json_dir . "/" . $labels['shipment_id'] . ".json");
        $label_request = json_decode($label_json_data, true);

        return view('shippingLabel.edit', compact('pickupAccounts', 'configuration', 'labels', 'order', 'label_request', 'payment_method', 'total_price'));
    }

    public function update(Request $request)
    {
        $shop = $this->shop;

        try {
            $current_datetime               = date('Y-m-d H:i:s');
            $pickup_account_id              = $request->pickupAccount;
            $handover_method                = $request->handoverMethod;
            $handover_method                = (int) $handover_method;
            $pickup_date                    = $request->pickupDate;

            if ($handover_method == 1) {
                $pickup_date                = null;
            } else {
                $pickup_date                = $this->format_pickup_date(date('Y-m-d H:i:s', strtotime($pickup_date)));
            }

            $consolidated_label             = "Y";
            $shipment_id                    = $request->shipmentID;

            $package_description            = $request->packageDescription;
            $product_code                   = $request->productCode;
            $cash_on_delivery               = $request->cashOnDelivery;
            $shipment_value_protection      = $request->shipmentValueProtection;
            $paper_proof_delivery_option    = $request->paperProofDeliveryOption;
            $open_box                       = $request->openBox;
            $currency                       = $request->currency;
            $remarks                        = $request->remarks;
            $multi_pieces_shipment          = $request->multiPiecesShipment;
            $is_mps_edit                    = $request->isMpsEdit;

            if ($multi_pieces_shipment == 'true') {
                $delivery_option            = $request->deliveryOption;
                $shipment_pieces            = $request->shipmentPieces;
                $shipment_pieces            = json_decode($shipment_pieces, true);
            }

            $order                          = $request->order;
            $return_mode                    = $request->returnMode;
            $return_address                 = $request->returnAddress;
            $shipment_weight                = $request->shipmentWeight;

            if ($handover_method == 2) {
                if (empty($pickup_date)) {
                    throw new Exception("Please enter pickup date.");
                }
            }

            if (empty($pickup_account_id)) {
                throw new Exception("Please select pickup account.");
            }

            if (empty($package_description)) {
                throw new Exception("Please enter package description.");
            }

            if (empty($return_mode)) {
                throw new Exception("Please enter return mode.");
            }

            if (empty($shipment_weight)) {
                throw new Exception("Please enter Shipment Weight.");
            }

            $configuration                  = $this->configuration;
            $pickup_account                 = Pickup::where('shop', $this->shop->name)->where('id', $pickup_account_id)->where('status', '1')->first()->toArray();
            $access_token                   = $configuration['access_token'];
            $dhl_auth_response              = $this->dhl_get_api_call('https://api.dhlecommerce.dhl.com' . '/rest'. '/v1/OAuth/AccessToken', [
                'clientId'      => $configuration['client_id'],
                'password'      => $configuration['client_secret'],
                'returnFormat'  => 'json'
            ]);

            if (isset($dhl_auth_response['accessTokenResponse']) && !empty($dhl_auth_response['accessTokenResponse'])) {
                $access_token_response = $dhl_auth_response['accessTokenResponse'];
                if (isset($access_token_response['token']) && !empty($access_token_response['token'])) {
                    $access_token = $access_token_response['token'];
                }
            }

            $hdr = [
                'messageType'       => 'EDITSHIPMENT',
                'messageDateTime'   => '2022-09-02T13:01:13+05:30',
                'accessToken'       => $access_token,
                'messageVersion'    => '1.4',
                'messageLanguage'   => 'en'
            ];

            $bd = [
                'inlineLabelReturn'         => 'Y',
                'customerAccountId'         => null,
                'pickupAccountId'           => isset($pickup_account['number']) ? $pickup_account['number'] : '',
                'soldToAccountId'           => isset($configuration['soldto_account']) ? $configuration['soldto_account'] : '',
                'handoverMethod'            => isset($handover_method) ? $handover_method : 1,
                'pickupDateTime'            => $pickup_date,
                'consolidatedLabelRequired' => $consolidated_label,
                'pickupAddress'             => [
                    'companyName'   => $pickup_account['company'],
                    'name'          => $pickup_account['name'],
                    'address1'      => $pickup_account['address_line_1'],
                    'address2'      => $pickup_account['address_line_2'],
                    'city'          => $pickup_account['city'],
                    'state'         => isset($pickup_account['state']) && !empty($pickup_account['state']) ? $pickup_account['state'] : null,
                    'district'      => isset($pickup_account['district']) && !empty($pickup_account['district']) ? $pickup_account['district'] : null,
                    'country'       => $configuration['country'],
                    'postCode'      => $pickup_account['postcode'],
                    'phone'         => $pickup_account['phone'],
                    'email'         => $pickup_account['email']
                ]
            ];


            $shipment_item = [
                'consigneeAddress'  => [
                    'companyName'      => (isset($order['shippingAddress']['company'])
                        ? $order['shippingAddress']['company'] : (isset($order['billingAddress']['company'])
                            ? $order['billingAddress']['company'] : null)),
                    'name'      => (isset($order['shippingAddress']['name'])
                        ? $order['shippingAddress']['name'] : (isset($order['billingAddress']['name'])
                            ? $order['billingAddress']['name'] : null)),
                    'address1'  => (isset($order['shippingAddress']['address1'])
                        ? $order['shippingAddress']['address1'] : (isset($order['billingAddress']['address1'])
                            ? $order['billingAddress']['address1'] : null)),
                    'address2'  => (isset($order['shippingAddress']['address2'])
                        ? $order['shippingAddress']['address2'] : (isset($order['billingAddress']['address2'])
                            ? $order['billingAddress']['address2'] : null)),
                    'address3'  => (isset($order['shippingAddress']['address3'])
                        ? $order['shippingAddress']['address3'] : (isset($order['billingAddress']['address3'])
                            ? $order['billingAddress']['address3'] : null)),
                    'city'      => (isset($order['shippingAddress']['city'])
                        ? $order['shippingAddress']['city'] : (isset($order['billingAddress']['city'])
                            ? $order['billingAddress']['city'] : null)),
                    'state'     => (isset($order['shippingAddress']['province'])
                        ? $order['shippingAddress']['province'] : (isset($order['billingAddress']['province'])
                            ? $order['billingAddress']['province'] : null)),
                    'district'  => null,
                    'country'   => (isset($order['shippingAddress']['countryCodeV2'])
                        ? ($order['shippingAddress']['countryCodeV2'] == 'IN' ? 'MY' : $order['shippingAddress']['countryCodeV2']) : (isset($order['billingAddress']['countryCodeV2'])
                            ? ($order['billingAddress']['countryCodeV2'] == 'IN' ? 'MY' : $order['billingAddress']['countryCodeV2']) : null)),
                    'postCode'  => (isset($order['shippingAddress']['zip'])
                        ? $order['shippingAddress']['zip'] : (isset($order['billingAddress']['zip'])
                            ? $order['billingAddress']['zip'] : null)),
                    'phone'     => isset($order['shippingAddress']['phone']) ? $order['shippingAddress']['phone'] : $order['customer']['phone'],
                    'email'     => isset($order['customer']['email']) ? $order['customer']['email'] : null,
                    'idNumber'  => null,
                    'idType'    => null,
                ],
                'shipmentID'        => $shipment_id,
                'returnMode'        => $return_mode,
                'packageDesc'       => substr($package_description, 0, 50),
                'totalWeight'       => (int) $shipment_weight,
                'totalWeightUOM'    => 'G',
                'productCode'       => $product_code,
                'codValue'          => !empty($cash_on_delivery) ? floatval($cash_on_delivery) : null,
                'insuranceValue'    => !empty($shipment_value_protection) ? floatval($shipment_value_protection) : null,
                'currency'          => $currency,
                'remarks'           => isset($remarks) && !empty($remarks) ? $remarks : null,
                'isMult'            => $multi_pieces_shipment,
                'isMpsEdit'         => $is_mps_edit
            ];
            if ($paper_proof_delivery_option == '1' && $open_box == '1') {
                $shipment_item['valueAddedServices'] = [
                    'valueAddedService' => [
                        ['vasCode' => 'PPOD'],
                        ['vasCode' => 'OBOX']
                    ]
                ];
            } else {
                if ($paper_proof_delivery_option == '1') {
                    $shipment_item['valueAddedServices'] = [
                        'valueAddedService' => [['vasCode' => 'PPOD']]
                    ];
                }

                if ($open_box == '1') {
                    $shipment_item['valueAddedServices'] = [
                        'valueAddedService' => [['vasCode' => 'OBOX']]
                    ];
                }
            }

            if ($return_mode == "02") {
                $shipment_item['returnAddress'] = [
                    'companyName'   => $pickup_account['company'],
                    'name'          => $pickup_account['name'],
                    'address1'      => $pickup_account['address_line_1'],
                    'address2'      => $pickup_account['address_line_2'],
                    'address3'      => $pickup_account['address_line_3'],
                    'city'          => $pickup_account['city'],
                    'state'         => isset($pickup_account['state']) && !empty($pickup_account['state']) ? $pickup_account['state'] : null,
                    'district'      => isset($pickup_account['district']) && !empty($pickup_account['district']) ? $pickup_account['district'] : null,
                    'country'       => $configuration['country'],
                    'postCode'      => $pickup_account['postcode'],
                    'phone'         => $pickup_account['phone'],
                    'email'         => $pickup_account['email']
                ];
            } elseif ($return_mode == "03") {
                $shipment_item['returnAddress'] = [
                    'companyName'   => $return_address['companyName'],
                    'name'          => $return_address['name'],
                    'address1'      => $return_address['address1'],
                    'address2'      => $return_address['address2'],
                    'address3'      => $return_address['address3'],
                    'city'          => $return_address['city'],
                    'state'         => isset($return_address['state']) && !empty($return_address['state']) ? $return_address['state'] : null,
                    'district'      => isset($return_address['district']) && !empty($return_address['district']) ? $return_address['district'] : null,
                    'country'       => $return_address['country'],
                    'postCode'      => $return_address['postcode'],
                    'phone'         => $return_address['phone'],
                    'email'         => $return_address['email']
                ];
            }

            if (isset($delivery_option) && !empty($delivery_option)) {
                $shipment_item['deliveryOption'] = $delivery_option;
            }

            if (isset($shipment_pieces) && !empty($shipment_pieces)) {
                $shipment_item['shipmentPieces'] = $shipment_pieces;
            }

            $shipment_items         = [$shipment_item];
            $bd['shipmentItems']    = $shipment_items;

            $label = [
                'pageSize'  => '400x600',
                'layout'    => isset($configuration['label_template']) && $configuration['label_template'] == '1' ? '1x1' : '4x1',
                'format'    => isset($configuration['label_format']) && !empty($configuration['label_format']) ? $configuration['label_format'] : 'PNG',
            ];

            $bd['label']    = $label;

            $request_data = [
                'labelRequest'  => ['hdr'   => $hdr, 'bd'    => $bd]
            ];

            Storage::disk('local')->put($this->requested_data_dir . "/" . $shipment_id . ".json", json_encode($request_data));
            $dhl_label_response = $this->dhl_post_api_call('https://api.dhlecommerce.dhl.com' . '/rest/v2/Label/Edit', $request_data);
            Storage::disk('local')->put($this->api_response_dir . "/" . $shipment_id . ".json", json_encode($dhl_label_response));

            if (isset($dhl_label_response['labelResponse']['bd']['labels'][0]['responseStatus'])) {
                $labels     = $dhl_label_response['labelResponse']['bd']['labels'];

                if (!empty($labels) && isset($labels[0]) && isset($labels[0]['responseStatus'])) {
                    $label              = $labels[0];
                    $response_status    = $label['responseStatus'];

                    if (isset($response_status['message']) && $response_status['message'] == 'SUCCESS') {
                        $shipment_id                = $label['shipmentID'];
                        $delivery_confirmation_no   = isset($label['deliveryConfirmationNo']) ? $label['deliveryConfirmationNo'] : '';
                        $content                    = $label['content'];
                        $ppod_content               = $paper_proof_delivery_option == '1' && isset($label['ppodLabel']) ? $label['ppodLabel'] : '';

                        if ($multi_pieces_shipment == 'true' && isset($label['pieces']) && !empty($label['pieces'])) {
                            $pieces                 = $label['pieces'];
                        }

                        $label_format               = isset($configuration['label_format']) && !empty($configuration['label_format']) ? $configuration['label_format'] : 'PNG';
                        $label_format_ext           = strtolower($label_format);
                        $image                      = $delivery_confirmation_no . "." . $label_format_ext;

                        if ($multi_pieces_shipment == 'true' && isset($pieces)) {
                            $generated_labels       = [];
                            foreach ($pieces as $piece) {
                                $image              = $shipment_id . "-" . $piece['shipmentPieceID'] . "." . $label_format_ext;

                                if ($label_format == "PNG" || $label_format == "ZPL") {
                                    Storage::disk('local')->put($this->labels_dir . '/' . $image, base64_decode($piece['content']));
                                } elseif ($label_format == "PDF") {
                                    $pdf_decoded    = base64_decode($content);
                                    $pdf_name       = Storage::disk('local')->path($this->labels_dir . '/' . $image);
                                    $pdf            = fopen($pdf_name, 'w');
                                    fwrite($pdf, $pdf_decoded);
                                    fclose($pdf);
                                }

                                $generated_labels[] = [
                                    "label"                     => $image,
                                    "delivery_confirmation_no"  => $piece['deliveryConfirmationNo'],
                                    "extension"                 => $label_format_ext
                                ];
                            }

                            if ($paper_proof_delivery_option == '1' && isset($pieces[0]['ppodLabel'])) {
                                $single_piece       = $pieces[0];
                                $ppod_image         = $shipment_id . "-PPOD." . $label_format_ext;
                                $ppod_content       = $single_piece['ppodLabel'];

                                if ($label_format == "PNG" || $label_format == "ZPL") {
                                    Storage::disk('local')->put($this->labels_dir . '/' . $ppod_image, base64_decode($ppod_content));
                                } elseif ($label_format == "PDF") {
                                    $ppod_pdf_decoded   = base64_decode($ppod_content);
                                    $ppod_pdf_name      = Storage::disk('local')->path($this->labels_dir . '/' . $ppod_image);
                                    $ppod_pdf           = fopen($ppod_pdf_name, 'w');
                                    fwrite($ppod_pdf, $ppod_pdf_decoded);
                                    fclose($ppod_pdf);
                                }

                                $generated_labels[0]['ppod'] = $ppod_image;
                            }
                        } else {
                            $image      = $shipment_id . "." . $label_format_ext;
                            $ppod_image = $shipment_id . "-PPOD." . $label_format_ext;

                            if ($label_format == "PNG" || $label_format == "ZPL") {
                                Storage::disk('local')->put($this->labels_dir . '/' . $image, base64_decode($content));
                                if (!empty($ppod_content)) {
                                    Storage::disk('local')->put($this->labels_dir . '/' . $ppod_image, base64_decode($ppod_content));
                                }
                            } elseif ($label_format == "PDF") {
                                $pdf_decoded    = base64_decode($content);
                                $pdf_name       = Storage::disk('local')->path($this->labels_dir . '/' . $image);
                                $pdf            = fopen($pdf_name, 'w');
                                fwrite($pdf, $pdf_decoded);
                                fclose($pdf);

                                if (!empty($ppod_content)) {
                                    $ppod_pdf_decoded   = base64_decode($ppod_content);
                                    $ppod_pdf_name      = Storage::disk('local')->path($this->labels_dir . '/' . $ppod_image);
                                    $ppod_pdf           = fopen($ppod_pdf_name, 'w');
                                    fwrite($ppod_pdf, $ppod_pdf_decoded);
                                    fclose($ppod_pdf);
                                }
                            }

                            $generated_labels = [[
                                "label"                     => $image,
                                "ppod"                      => $paper_proof_delivery_option == '1' ? $ppod_image : '',
                                "delivery_confirmation_no"  => $delivery_confirmation_no,
                                "extension"                 => $label_format_ext
                            ]];
                        }

                        Storage::disk('local')->put($this->label_json_dir . "/" . $shipment_id . ".json", json_encode($request_data));

                        $is_updated = Label::where([
                            'shop'                      => $this->shop->name,
                            'order_id'                  => $order['id']
                        ])->update([
                            'shipment_id'               => $shipment_id,
                            'delivery_confirmation_no'  => $delivery_confirmation_no,
                            'content'                   => "",
                            'image'                     => $image,
                            'generated_labels'          => !empty($generated_labels) ? json_encode($generated_labels) : '',
                            'updated_at'              => $current_datetime
                        ]);

                        if (empty($is_updated)) {
                            $newLabel                           = new Label;
                            $newLabel->shop                     = $this->shop->name;
                            $newLabel->order_id                 = $order['id'];
                            $newLabel->order_name               = $order['name'];
                            $newLabel->shipment_id              = $shipment_id;
                            $newLabel->delivery_confirmation_no = $delivery_confirmation_no;
                            $newLabel->content                  = "";
                            $newLabel->image                    = $image;
                            $newLabel->generated_labels         = !empty($generated_labels) ? json_encode($generated_labels) : '';
                            $newLabel->created_at               = $current_datetime;
                            $newLabel->updated_at               = $current_datetime;
                            $newLabel->save();
                        }

                        $this->updateTrackingInfo($order['id'],$shipment_id);

                        return response()->json(['status' => true, 'message' => "Label edit successfully."]);
                    } else {
                        throw new Exception($response_status['messageDetails'][0]['messageDetail']);
                    }
                }
            } else {
                if (isset($dhl_label_response['labelResponse']['bd']['responseStatus'])) {
                    $bd_response = $dhl_label_response['labelResponse']['bd']['responseStatus'];
                    if ($bd_response['code'] != '200' || $bd_response['message'] != 'SUCCESS') {
                        return response()->json(['status' => false, 'message' => $bd_response['messageDetails'][0]['messageDetail']]);
                    }
                }
            }
        } catch (Exception $ex) {
            return response()->json(['status' => false, 'message' => $ex->getMessage()]);
        }
    }

    public function delete(Request $request)
    {
        try {
            $shipment_id        = $request->shipmentId;
            $order_id           = $request->orderId;
            $label_json_data    = Storage::disk('local')->get($this->label_json_dir . "/" . $shipment_id . ".json");
            $label_request      = json_decode($label_json_data, true);

            $configuration      = $this->configuration;
            $dhl_auth_response  = $this->dhl_get_api_call(config('services.dhl.api_base_url').'/rest'.'/v1/OAuth/AccessToken', [
                'clientId'      => $configuration['client_id'],
                'password'      => $configuration['client_secret'],
                'returnFormat'  => 'json'
            ]);

            if (isset($dhl_auth_response['accessTokenResponse']) && !empty($dhl_auth_response['accessTokenResponse'])) {
                $access_token_response  = $dhl_auth_response['accessTokenResponse'];
                if (isset($access_token_response['token']) && !empty($access_token_response['token'])) {
                    $access_token       = $access_token_response['token'];
                }
            }

            $hdr = [
                'messageType'       => 'DELETESHIPMENT',
                'messageDateTime'   => '2022-09-02T13:01:13+05:30',
                'accessToken'       => $access_token,
                'messageVersion'    => '1.4',
                'messageLanguage'   => 'en'
            ];

            $bd = [
                'pickupAccountId'   => $label_request['labelRequest']['bd']['pickupAccountId'],
                'soldToAccountId'   => $label_request['labelRequest']['bd']['soldToAccountId'],
                "shipmentItems"     => [["shipmentID" => $shipment_id]]
            ];

            $request_data = [
                'deleteShipmentReq' => ['hdr' => $hdr, 'bd' => $bd]
            ];

            $this->dhl_post_api_call(config('services.dhl.api_base_url') . '/rest/v2/Label/Delete', $request_data);

            Label::where([
                'shop'          => $this->shop->name,
                'order_id'      => $order_id,
                'shipment_id'   => $shipment_id
            ])->delete();

            echo "
            <script>
            window.parent.location.href = 'https://admin.shopify.com/store/" . explode('.', $this->shop->name)[0] . "/apps/" . config('services.shopify-app.handle') . "/shipping-label/list?deleted=yes';
            </script>
            ";
            exit();
        } catch (Exception $ex) {
            echo "
            <script>
            window.parent.location.href = 'https://admin.shopify.com/store/" . explode('.', $this->shop->name)[0] . "/apps/" . config('services.shopify-app.handle') . "/shipping-label/view/" . $request->orderId . "?deleted=failed';
            </script>
            ";
            exit();
        }
    }

    public function bulkCreate(Request $request)
    {
        // Build orders list (from ids passed either via GET or POST)
        $order_ids = $request->ids ?? [];
        $orders = [];
        if (!empty($order_ids) && is_array($order_ids)) {
            foreach ($order_ids as $oid) {
                $orderData = $this->getOrderDetails($oid);
                if (!isset($orderData['data']['order'])) {
                    continue;
                }
                $edge = $orderData['data']['order'];
                $weight = $this->calculateVariantWeights($edge['lineItems']['edges']);
                $gidParts = explode('/', $edge['id']);
                $orders[] = [
                    'id' => end($gidParts),
                    'name' => $edge['name'],
                    'createdAt' => $edge['createdAt'],
                    'shippingAddress' => $edge['shippingAddress'],
                    'billingAddress' => $edge['billingAddress'],
                    'weight' => $weight,
                    'destination' => $edge['shippingAddress']['countryCodeV2'] ?? null,
                ];
            }
        }

        $pickup_accounts = $this->pickupAccounts;
        $configArray = $this->configuration;

        $pickup_account = Pickup::where('shop', $this->shop->name)
            ->where('status', '1')
            ->where('is_default', '1')
            ->first();

        $shipping_products = [];
        if ($pickup_account && !empty($configArray['product_code'])) {
            $productNames = [
                'PDO' => 'Parcel Domestic',
                'PDR' => 'DHL Parcel Return',
                'PDE' => 'Parcel Domestic Expedited',
                'DDO' => 'Document Domestic',
                'SDP' => 'DHL Parcel Metro',
            ];
            $code = $configArray['product_code'];
            $shipping_products[] = [
                'code' => $code,
                'name' => $productNames[$code] ?? $code,
                'accountName' => $pickup_account->company,
                'accountNo' => $pickup_account->number,
            ];
        }

        $value_added_services = [
            ['code' => 'INS', 'label' => 'Additional insurance'],
            ['code' => 'OBOX', 'label' => 'Open Box'],
            ['code' => 'PPOD', 'label' => 'Paper Proof of Delivery'],
            ['code' => 'COD', 'label' => 'Cash on Delivery']
        ];

        // If GET request or no ids, render UI only
        if ($request->isMethod('get') || empty($order_ids)) {
            // Optional fulfillment banner on GET if ids are present but none are fulfilled
            $noFulfillments = false;
            if (!empty($order_ids)) {
                $fulfilledCount = 0;
                foreach ($order_ids as $oid) {
                    $row = $this->getOrderRowData($oid);
                    $status = strtolower($row['order']['fulfillment_status'] ?? '');
                    if ($status === 'fulfilled') { $fulfilledCount++; }
                }
                $noFulfillments = ($fulfilledCount === 0);
            }
            return view('shippingLabel.bulk-create', [
                'orders' => $orders,
                'pickup_accounts' => $pickup_accounts,
                'configuration' => $configArray,
                'shipping_products' => $shipping_products,
                'value_added_services' => $value_added_services,
                'labelsGenerated' => session('labelsGenerated', false),
                'downloadURL' => session('downloadURL', ''),
                'mergedURL' => session('mergedURL', ''),
                'labels_dir' => $this->labels_dir,
                'prefix' => $configArray['prefix'] ?? '',
                'noFulfillments' => $noFulfillments,
            ])->with('activePickup', $pickup_account);
        }

        // POST: validate minimal selection and then generate labels
        if (empty($order_ids) || !is_array($order_ids)) {
            return redirect()->route('shippinglabel.bulk-create', [
                'shop' => $request->query('shop') ?? $request->input('shop'),
            ])->with('error', 'Please select at least one order to create labels.');
        }

        $selected_services = $request->input('services', []);
        if (!is_array($selected_services)) {
            $selected_services = [];
        }

        // POST: generate labels for selected orders (merged former bulkGenerate)
        // Fulfillment pre-check: if none of the selected orders are fulfilled, show banner
        $fulfilledAny = false;
        foreach ($order_ids as $oid) {
            $row = $this->getOrderRowData($oid);
            $status = strtolower($row['order']['fulfillment_status'] ?? '');
            if ($status === 'fulfilled') { $fulfilledAny = true; break; }
        }
        if (!$fulfilledAny) {
            return view('shippingLabel.bulk-create', [
                'orders' => $orders,
                'pickup_accounts' => $pickup_accounts,
                'configuration' => $configArray,
                'shipping_products' => $shipping_products,
                'value_added_services' => $value_added_services,
                'labelsGenerated' => false,
                'downloadURL' => '',
                'mergedURL' => '',
                'labels_dir' => $this->labels_dir,
                'prefix' => $configArray['prefix'] ?? '',
                'noFulfillments' => true,
            ])->with('activePickup', $pickup_account);
        }
        $bulk_response = [];
        $current_datetime = date('Y-m-d H:i:s');
        $prefix = $configArray['prefix'] ?? '';
        $currency = $configArray['currency'] ?? '';
        $handover_method = 1;
        $pickup_date = null;
        $consolidated_label = 'Y';
        $product_code = $configArray['product_code'] ?? 'PDO';
        $cash_on_delivery = null;
        $shipment_value_protection = null;
        $multi_pieces_shipment = 'false';
        $return_mode = '01';

        $pickup_account_row = Pickup::where('shop', $this->shop->name)
            ->where('id', $configArray['pickup_account'] ?? null)
            ->where('status', '1')
            ->first();

        $access_token = $configArray['access_token'] ?? '';
        $dhl_auth_response = $this->dhl_get_api_call(config('services.dhl.api_base_url') . '/rest/v1' . '/OAuth/AccessToken', [
            'clientId' => $configArray['client_id'] ?? '',
            'password' => $configArray['client_secret'] ?? '',
            'returnFormat' => 'json'
        ]);
        if (!empty($dhl_auth_response['accessTokenResponse']['token'])) {
            $access_token = $dhl_auth_response['accessTokenResponse']['token'];
        }

        $hdr = [
            'messageType' => 'LABEL',
            'messageDateTime' => '2022-09-02T13:01:13+05:30',
            'accessToken' => $access_token,
            'messageVersion' => '1.4',
            'messageLanguage' => 'en',
            'messageSource' => 'Shopify'
        ];

        $label_format = isset($configArray['label_format']) && !empty($configArray['label_format']) ? $configArray['label_format'] : 'PNG';

        $bd = [
            'inlineLabelReturn' => 'Y',
            'customerAccountId' => null,
            'pickupAccountId' => $pickup_account_row ? ($pickup_account_row->number ?? '') : '',
            'soldToAccountId' => $configArray['soldto_account'] ?? '',
            'handoverMethod' => $handover_method,
            'pickupDateTime' => $pickup_date,
            'consolidatedLabelRequired' => $consolidated_label,
            'pickupAddress' => $pickup_account_row ? [
                'companyName' => $pickup_account_row->company,
                'name' => $pickup_account_row->name,
                'address1' => $pickup_account_row->address_line_1,
                'address2' => $pickup_account_row->address_line_2,
                'city' => $pickup_account_row->city,
                'state' => $pickup_account_row->state,
                'district' => $pickup_account_row->district ?: null,
                'country' => $configArray['country'] ?? '',
                'postCode' => $pickup_account_row->postcode,
                'phone' => $pickup_account_row->phone,
                'email' => $pickup_account_row->email
            ] : [],
            'label' => [
                'pageSize' => '400x600',
                'layout' => isset($configArray['label_template']) && $configArray['label_template'] == '1' ? '1x1' : '4x1',
                'format' => $label_format,
            ]
        ];

        $labels = [];
        $individual_files = [];
        $bulk_response = [];
        $any_success = false;
        
        // Collect individual files
        if (!empty($order_ids) && is_array($order_ids)) {
            foreach ($order_ids as $selected_order_id) {
                $global_order_id = null;
                try {
                    $order_data = $this->getOrderDetails($selected_order_id);
                    $getOrderRowData = $this->getOrderRowData($selected_order_id)['order'];
                    $order_edge = $order_data['data']['order'];
                    $weight = $this->calculateVariantWeights($order_edge['lineItems']['edges']);
                    $global_order_id = $order_edge['name'];

                    if (!$pickup_account_row) {
                        throw new Exception("Couldn't find pickup account, Please select it from Configuration and Try again.!!");
                    }

                    $label_data = Label::where('shop', $this->shop->name)->where('order_id', $selected_order_id)->first();
                    if (!empty($label_data)) {
                        throw new Exception("Label already generated");
                    }

                    if (!empty($order_edge)) {
                        $order_id_parts = explode('/', $order_edge['id']);
                        $order_info = [
                            'id' => end($order_id_parts),
                            'name' => $order_edge['name'],
                            'currencyCode' => $order_edge['currencyCode'],
                            'createdAt' => $order_edge['createdAt'],
                            'destination' => $order_edge['shippingAddress']['countryCodeV2'] ?? '',
                        ];

                        $shipment_id = ($configArray['prefix'] ?? '') . substr($order_info['name'], 1);

                        // Determine per-order COD applicability
                        $payment_gateways = isset($getOrderRowData['payment_gateway_names']) && is_array($getOrderRowData['payment_gateway_names']) ? $getOrderRowData['payment_gateway_names'] : [];
                        $gatewayValue = strtolower($getOrderRowData['gateway'] ?? '');
                        $isOrderCOD = false;
                        foreach ($payment_gateways as $pg) {
                            if (stripos($pg, 'cod') !== false || stripos($pg, 'cash on delivery') !== false) {
                                $isOrderCOD = true;
                                break;
                            }
                        }
                        if (!$isOrderCOD && $gatewayValue === 'cod') {
                            $isOrderCOD = true;
                        }

                        $codSelected = in_array('COD', $selected_services ?? []);
                        $codValueForOrder = null;
                        $vasForOrder = array_values(array_filter($selected_services ?? [], function($code) { return $code !== 'COD'; }));
                        if ($codSelected && $isOrderCOD) {
                            $codValueForOrder = isset($getOrderRowData['total_price']) ? floatval($getOrderRowData['total_price']) : null;
                            if (!empty($codValueForOrder)) {
                                $vasForOrder[] = 'COD';
                            }
                        }
                        if ($codSelected && !$isOrderCOD) {
                            $bulk_response[] = [
                                'order_id' => $global_order_id,
                                'message' => 'COD removed for prepaid order.'
                            ];
                        }
                        $shipment_item = [
                            'consigneeAddress' => [
                                'companyName' => $order_edge['shippingAddress']['company'] ?? ($order_edge['billingAddress']['company'] ?? null),
                                'name' => $order_edge['shippingAddress']['name'] ?? ($order_edge['billingAddress']['name'] ?? null),
                                'address1' => $order_edge['shippingAddress']['address1'] ?? ($order_edge['billingAddress']['address1'] ?? null),
                                'address2' => $order_edge['shippingAddress']['address2'] ?? ($order_edge['billingAddress']['address2'] ?? null),
                                'address3' => null,
                                'city' => $order_edge['shippingAddress']['city'] ?? ($order_edge['billingAddress']['city'] ?? null),
                                'state' => $order_edge['shippingAddress']['province'] ?? ($order_edge['billingAddress']['province'] ?? null),
                                'district' => null,
                                'country' => $order_edge['shippingAddress']['countryCodeV2'] ?? ($order_edge['billingAddress']['countryCodeV2'] ?? null),
                                'postCode' => $order_edge['shippingAddress']['zip'] ?? ($order_edge['billingAddress']['zip'] ?? null),
                                'phone' => $order_edge['shippingAddress']['phone'] ?? ($order_edge['customer']['phone'] ?? null),
                                'email' => $order_edge['customer']['email'] ?? null,
                                'idNumber' => null,
                                'idType' => null,
                            ],
                            'shipmentID' => $shipment_id,
                            'returnMode' => $return_mode,
                            'packageDesc' => substr('Order '.$order_info['name'], 0, 50),
                            'totalWeight' => (int) max(1, round(($weight ?: 0))),
                            'totalWeightUOM' => 'G',
                            'productCode' => $product_code,
                            'codValue' => $codValueForOrder,
                            'insuranceValue' => !empty($shipment_value_protection) ? floatval($shipment_value_protection) : null,
                            'currency' => $currency,
                            'remarks' => null,
                            'isMult' => $multi_pieces_shipment,
                            'returnAddress' => [
                                'companyName' => $pickup_account_row->company ?? '',
                                'name' => $pickup_account_row->name ?? '',
                                'address1' => $pickup_account_row->address_line_1 ?? '',
                                'address2' => $pickup_account_row->address_line_2 ?? '',
                                'address3' => $pickup_account_row->address_line_3 ?? null,
                                'city' => $pickup_account_row->city ?? '',
                                'state' => $pickup_account_row->state ?? null,
                                'district' => $pickup_account_row->district ?? null,
                                'country' => $configArray['country'] ?? '',
                                'postCode' => $pickup_account_row->postcode ?? '',
                                'phone' => $pickup_account_row->phone ?? '',
                                'email' => $pickup_account_row->email ?? '',
                            ],
                        ];

                        if (!empty($vasForOrder)) {
                            $shipment_item['valueAddedServices'] = [
                                'valueAddedService' => array_values(array_map(function ($code) {
                                    return ['vasCode' => $code];
                                }, $vasForOrder))
                            ];
                        }

                        $shipment_items = [$shipment_item];
                        $bd['shipmentItems'] = $shipment_items;
                        $request_data = [
                            'labelRequest' => ['hdr' => $hdr, 'bd' => $bd]
                        ];

                        Storage::disk('local')->put($this->requested_data_dir . "/" . $shipment_id . ".json", json_encode($request_data));
                        $dhl_label_response = $this->dhl_post_api_call(config('services.dhl.api_base_url') . '/rest/v2/Label', $request_data);
                        Storage::disk('local')->put($this->api_response_dir . "/" . $shipment_id . ".json", json_encode($dhl_label_response));

                        if (isset($dhl_label_response['labelResponse']['bd']['labels'][0]['responseStatus'])) {
                            $labels_response = $dhl_label_response['labelResponse']['bd']['labels'];
                            if (!empty($labels_response) && isset($labels_response[0]) && isset($labels_response[0]['responseStatus'])) {
                                $any_success = true;
                                $label = $labels_response[0];
                                $response_status = $label['responseStatus'];
                                if (isset($response_status['message']) && $response_status['message'] == 'SUCCESS') {
                                    $shipment_id = $label['shipmentID'];
                                    $delivery_confirmation_no = $label['deliveryConfirmationNo'];
                                    $content = $label['content'];
                                    $ppod_content = isset($label['ppodLabel']) ? $label['ppodLabel'] : '';
                                    if (isset($label['pieces']) && !empty($label['pieces']) && isset($label['pieces'][0]) && !empty($label['pieces'][0])) {
                                        $pieces = $label['pieces'][0];
                                        $delivery_confirmation_no = $pieces['deliveryConfirmationNo'];
                                        $content = $pieces['content'];
                                        if (isset($pieces['ppodLabel']) && !empty($pieces['ppodLabel'])) {
                                            $ppod_content = $pieces['ppodLabel'];
                                        }
                                    }

                                    $label_format_ext = strtolower($label_format);
                                    $image = $shipment_id . "." . $label_format_ext;
                                    $ppod_image = $shipment_id . "-PPOD." . $label_format_ext;

                                    if ($label_format == "PNG" || $label_format == "ZPL") {
                                        Storage::disk('local')->put($this->labels_dir . '/' . $image, base64_decode($content));
                                        $label_path = Storage::disk('local')->path($this->labels_dir . '/' . $image);
                                        $individual_files[] = $label_path;
                                        if (!empty($ppod_content)) {
                                            Storage::disk('local')->put($this->labels_dir . '/' . $ppod_image, base64_decode($ppod_content));
                                            $ppod_path = Storage::disk('local')->path($this->labels_dir . '/' . $ppod_image);
                                            $individual_files[] = $ppod_path;
                                            $generated_ppod = $ppod_image;
                                        } else {
                                            $generated_ppod = '';
                                        }
                                    } elseif ($label_format == "PDF") {
                                        $pdf_decoded = base64_decode($content);
                                        $pdf_name = Storage::disk('local')->path($this->labels_dir . '/' . $image);
                                        $pdf = fopen($pdf_name, 'w');
                                        fwrite($pdf, $pdf_decoded);
                                        fclose($pdf);

                                        $generated_ppod = '';
                                        if (!empty($ppod_content)) {
                                            $ppod_pdf_decoded = base64_decode($ppod_content);
                                            $ppod_path = Storage::disk('local')->path($this->labels_dir . '/' . $ppod_image);
                                            $ppod_pdf = fopen($ppod_path, 'w');
                                            fwrite($ppod_pdf, $ppod_pdf_decoded);
                                            fclose($ppod_pdf);

                                            try {
                                                $combined_path = Storage::disk('local')->path($this->labels_dir . '/' . $shipment_id . '.pdf');
                                                $merger = new Fpdi();
                                                $label_pages = $merger->setSourceFile($pdf_name);
                                                for ($p=1;$p<=$label_pages;$p++) { $merger->AddPage(); $tpl=$merger->importPage($p); $merger->useTemplate($tpl); }
                                                $ppod_pages = $merger->setSourceFile($ppod_path);
                                                for ($p=1;$p<=$ppod_pages;$p++) { $merger->AddPage(); $tpl=$merger->importPage($p); $merger->useTemplate($tpl); }
                                                $merger->Output($combined_path, 'F');
                                                $image = $shipment_id . '.pdf';
                                                @unlink($pdf_name);
                                                @unlink($ppod_path);
                                                $label_path = $combined_path;
                                            } catch (\Exception $e) {
                                                \Log::error('Failed to merge PPOD for shipment ' . $shipment_id . ': ' . $e->getMessage());
                                                $label_path = $pdf_name;
                                                $individual_files[] = $ppod_path;
                                            }
                                        } else {
                                            $label_path = $pdf_name;
                                        }
                                        $individual_files[] = $label_path;
                                    }

                                    $generated_labels = [[
                                        'label' => $image,
                                        'ppod' => $generated_ppod,
                                        'delivery_confirmation_no' => $delivery_confirmation_no,
                                        'extension' => $label_format_ext,
                                    ]];

                                    Storage::disk('local')->put($this->label_json_dir . "/" . $shipment_id . ".json", json_encode($request_data));

                                    $is_updated = Label::where([
                                        'shop' => $this->shop->name,
                                        'order_id' => $order_info['id']
                                    ])->update([
                                        'shipment_id' => $shipment_id,
                                        'delivery_confirmation_no' => $delivery_confirmation_no,
                                        'content' => '',
                                        'image' => $image,
                                        'generated_labels' => !empty($generated_labels) ? json_encode($generated_labels) : '',
                                        'updated_at' => $current_datetime
                                    ]);
                                    if (empty($is_updated)) {
                                        $newLabel = new Label;
                                        $newLabel->shop = $this->shop->name;
                                        $newLabel->order_id = $order_info['id'];
                                        $newLabel->order_name = $order_info['name'];
                                        $newLabel->shipment_id = $shipment_id;
                                        $newLabel->delivery_confirmation_no = $delivery_confirmation_no;
                                        $newLabel->content = '';
                                        $newLabel->image = $image;
                                        $newLabel->generated_labels = !empty($generated_labels) ? json_encode($generated_labels) : '';
                                        $newLabel->created_at = $current_datetime;
                                        $newLabel->updated_at = $current_datetime;
                                        $newLabel->save();
                                    }

                                    $bulk_response[] = [
                                        'order_id' => $global_order_id,
                                        'message' => 'Label has been generated successfully',
                                        'status' => 'success'
                                    ];
                                    $this->updateTrackingInfo($order_info['id'],$shipment_id);
                                } else {
                                    $error_message = $response_status['messageDetails'][0]['messageDetail'] ?? 'Unknown error';
                                    $error_code = $response_status['code'] ?? 'Unknown';
                                    $bulk_response[] = [
                                        'order_id' => $global_order_id,
                                        'message' => "$error_code - $error_message",
                                        'status' => 'error'
                                    ];
                                }
                            }
                        } else {
                            if (isset($dhl_label_response['labelResponse']['bd']['responseStatus'])) {
                                $bd_response = $dhl_label_response['labelResponse']['bd']['responseStatus'];
                                $error_message = $bd_response['messageDetails'][0]['messageDetail'] ?? 'Unknown error';
                                $error_code = $bd_response['code'] ?? 'Unknown';
                                $bulk_response[] = [
                                    'order_id' => $global_order_id,
                                    'message' => "$error_code - $error_message",
                                    'status' => 'error'
                                ];
                            }
                        }
                    }
                } catch (Exception $ex) {
                    $bulk_response[] = [
                        'order_id' => $global_order_id,
                        'message' => $ex->getMessage(),
                        'status' => 'error'
                    ];
                }
            }
        }

        // Fallback: if nothing was created in this request, collect existing files for selected orders
        if (empty($individual_files) && !empty($order_ids) && is_array($order_ids)) {
            foreach ($order_ids as $oid) {
                $labelRow = Label::where('shop', $this->shop->name)->where('order_id', $oid)->first();
                if (!$labelRow) { continue; }
                $labelData = $labelRow->toArray();
                $filenames = [];
                if ($labelData['image']) {
                    $filenames[] = $labelData['image'];
                }
                if (!empty($labelData['generated_labels'])) {
                    $gl = json_decode($labelData['generated_labels'], true);
                    foreach ($gl as $g) {
                        if (!empty($g['label'])) { $filenames[] = $g['label']; }
                        if (!empty($g['ppod'])) { $filenames[] = $g['ppod']; }
                    }
                }
                foreach ($filenames as $fn) {
                    $fp = Storage::disk('local')->path($this->labels_dir . '/' . $fn);
                    if (file_exists($fp)) {
                        $individual_files[] = $fp;
                    }
                }
            }
        }

        // Create merged PDF or ZIP for download under local storage
        $localTempRel = $this->shop_dir . "/temp_pdfs";
        $localTempAbs = Storage::disk('local')->path($localTempRel);
        if (!file_exists($localTempAbs)) {
            mkdir($localTempAbs, 0777, true);
        }
        $merged_pdf_path = $localTempAbs . "/DHLBulkLabels.pdf";
        $zip_path = $localTempAbs . "/DHLBulkLabels.zip";
        $mergedURL = "";
        $downloadURL = "";
        if ($label_format == "PDF") {
            // Create merged PDF for printing
            if (!empty($individual_files)) {
                try {
                    $pdf = new Fpdi();
                    foreach ($individual_files as $file) {
                        if (file_exists($file)) {
                            $pageCount = $pdf->setSourceFile($file);
                            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                                $pdf->AddPage();
                                $templateId = $pdf->importPage($pageNo);
                                $pdf->useTemplate($templateId);
                            }
                        }
                    }
                    $pdf->Output($merged_pdf_path, 'F');
                    $mergedURL = asset('storage/app/' . $localTempRel . "/DHLBulkLabels.pdf");                    
                    $downloadURL = $mergedURL;
                } catch (\Exception $e) {
                    \Log::error("Failed to merge PDFs. Error: " . $e->getMessage());
                }
            }
        } else {
            // Create ZIP for non-PDF formats
            if (!empty($individual_files)) {
                try {
                    $zip = new \ZipArchive();
                    if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                        foreach ($individual_files as $file) {
                            if (file_exists($file)) {
                                $zip->addFile($file, basename($file));
                            }
                        }
                        $zip->close();
                        $downloadURL = asset('storage/app/' . $localTempRel . "/DHLBulkLabels.zip");
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to create ZIP. Error: " . $e->getMessage());
                }
            }
        }

        // Store label generation state and bulk_response in session
        session([
            'labelsGenerated' => $any_success,
            'mergedURL' => $mergedURL,
            'downloadURL' => $downloadURL,
            'labels_dir' => $this->labels_dir,
            'prefix' => $prefix,
            'bulk_response' => $bulk_response
        ]);

        return view('shippingLabel.bulk-create', [
            'orders' => $orders,
            'pickup_accounts' => $pickup_accounts,
            'configuration' => $configArray,
            'shipping_products' => $shipping_products,
            'value_added_services' => $value_added_services,
            'labelsGenerated' => true,
            'downloadURL' => $downloadURL,
            'mergedURL' => $mergedURL,
            'labels_dir' => $this->labels_dir,
            'prefix' => $prefix,
            'bulk_response' => $bulk_response,
        ])->with('activePickup', $pickup_account);
    }
    
    public function bulkPrint(Request $request)
    {
        $order_ids = $request->ids;
        $labels = [];
        $pdfFiles = [];

        // Step 1: Collect all label files (images and PDFs)
        if (!empty($order_ids) && is_array($order_ids)) {
            foreach ($order_ids as $order_id) {
                $label_data = Label::where('shop', $this->shop->name)->where('order_id', $order_id)->first();

                if ($label_data) {
                    $label_data = $label_data->toArray();
                    $generated_labels = isset($label_data['generated_labels']) && !empty($label_data['generated_labels']) 
                        ? json_decode($label_data['generated_labels'], true) 
                        : [];

                    if (!empty($generated_labels)) {
                        foreach ($generated_labels as $generated_label) {
                            $label_path = Storage::disk('local')->path($this->shop_dir . "/labels/labels/" . $generated_label['label']);
                            if (file_exists($label_path)) {
                                $labels[] = [
                                    "file" => $label_path,
                                    "name" => $generated_label['label']
                                ];
                            }
                            if (isset($generated_label['ppod']) && !empty($generated_label['ppod'])) {
                                $ppod_path = Storage::disk('local')->path($this->shop_dir . "/labels/labels/" . $generated_label['ppod']);
                                if (file_exists($ppod_path)) {
                                    $labels[] = [
                                        "file" => $ppod_path,
                                        "name" => $generated_label['ppod']
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Step 2: Convert images to PDFs and collect all PDFs
        $temp_dir = Storage::disk('local')->path($this->shop_dir . "/temp_pdfs");
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }

        foreach ($labels as $index => $label) {
            $file_path = $label['file'];
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            $output_pdf = $temp_dir . "/label_{$index}.pdf";

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                try {
                    // Convert image to PDF using TCPDF
                    $pdf = new TCPDF();
                    $pdf->SetAutoPageBreak(false);
                    $pdf->AddPage();
                    $pdf->Image($file_path, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
                    $pdf->Output($output_pdf, 'F');
                    $pdfFiles[] = $output_pdf;
                } catch (\Exception $e) {
                    \Log::error("Failed to convert image to PDF: {$file_path}. Error: " . $e->getMessage());
                }
            } elseif ($extension === 'pdf') {
                // If already a PDF, use it directly
                $pdfFiles[] = $file_path;
            }
        }

        // Step 3: Merge all PDFs into a single PDF
        $merged_pdf_path = $temp_dir . "/DHLBulkLabels.pdf";
        try {
            $pdf = new Fpdi();
            foreach ($pdfFiles as $file) {
                if (file_exists($file)) {
                    $pageCount = $pdf->setSourceFile($file);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $pdf->AddPage();
                        $templateId = $pdf->importPage($pageNo);
                        $pdf->useTemplate($templateId);
                    }
                }
            }
            $pdf->Output($merged_pdf_path, 'F');
        } catch (\Exception $e) {
            \Log::error("Failed to merge PDFs. Error: " . $e->getMessage());
            return view('shippingLabel.print', ['downloadURL' => '', 'error' => 'Failed to merge PDFs']);
        }

        // Step 4: Create ZIP file containing the merged PDF
        $downloadURL = "";
        if (!empty($pdfFiles)) {
            $zip = new ZipArchive();
            $zipdir = Storage::disk('local')->path($this->zip_label_dir);

            if (!file_exists($zipdir)) {
                mkdir($zipdir, 0777, true);
            }

            $filename = $zipdir . "/bulkprintlabels.zip";

            if (file_exists($filename)) {
                @unlink($filename);
            }

            if ($zip->open($filename, ZipArchive::CREATE)) {
                if (file_exists($merged_pdf_path)) {
                    $zip->addFile($merged_pdf_path, "DHLBulkLabels.pdf");
                }
                $zip->close();

                $downloadURL = asset('storage/app/' . $this->zip_label_dir . "/bulkprintlabels.zip");
            }

            // Clean up temporary PDFs
            foreach ($pdfFiles as $file) {
                if (file_exists($file) && strpos($file, $temp_dir) !== false) {
                    @unlink($file);
                }
            }
            if (file_exists($merged_pdf_path)) {
                @unlink($merged_pdf_path);
            }
        }

        return view('shippingLabel.print', compact('downloadURL'));
    }

    public function format_pickup_date($date)
    {
        $tz = date_default_timezone_get();
        $date_time = date($date);
        $datetime = new \DateTime($date_time, new \DateTimeZone($tz));
        $pickup_datetime = $datetime->format('c');
        return $pickup_datetime;
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

    public function dhl_post_api_call($url, $data = [])
    {

        $curl = curl_init();
        $httpHeader[] = 'Content-Type: application/json';
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $httpHeader
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    public function calculateVariantWeights($variants)
    {
      $weight = 0;     
      foreach ($variants as $variant){
        $variantWeight = isset($variant['node']['variant']['weight']) ? $variant['node']['variant']['weight'] : 0 ;  
        $weightUnit = isset($variant['node']['variant']['weightUnit']) ? $variant['node']['variant']['weightUnit'] : 'GRAMS' ;  
        switch($weightUnit){
          case 'KILOGRAMS': 
            $weight +=  $variantWeight * 1000;
            break;
          case 'OUNCES':
            $weight += $variantWeight * 28.3495;
            break;
          case 'POUNDS':
            $weight +=  $variantWeight * 453.592;
            break;            
          default:
            $weight +=  $variantWeight;
        }
      }
      return $weight;
    }

}