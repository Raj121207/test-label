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
        // Handle GET requests (display form only)
        if ($request->isMethod('get') || empty($request->ids)) {
            $orders = [];
            $order_ids = $request->ids ?? [];
            
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

            // Get data from session for persistence
            $bulk_response = session('bulk_response', []);
            $generatedLabelIds = session('generatedLabelIds', []);
            $labelsGenerated = session('labelsGenerated', false);
            $downloadURL = session('downloadURL', '');

            // If we have orders but no generatedLabelIds in session, rebuild from database
            if (!empty($orders) && empty($generatedLabelIds)) {
                foreach ($orders as $order) {
                    $label = Label::where('shop', $this->shop->name)
                        ->where('order_id', $order['id'])
                        ->first();
                    if ($label) {
                        $generatedLabelIds[$order['name']] = $label->id;
                        // Update bulk_response to show success
                        $bulk_response[] = [
                            'order_id' => $order['name'],
                            'message' => 'Label has been generated successfully',
                            'status' => 'success'
                        ];
                    }
                }
                // Update session with rebuilt data
                session([
                    'bulk_response' => $bulk_response,
                    'generatedLabelIds' => $generatedLabelIds,
                    'labelsGenerated' => !empty($generatedLabelIds),
                ]);
            }

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
                'bulk_response' => $bulk_response,
                'generatedLabelIds' => $generatedLabelIds,
                'labelsGenerated' => $labelsGenerated,
                'downloadURL' => $downloadURL,
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
            'messageLanguage' => 'en'
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

        $labels = []; // To store label file paths for PDF merging
        $orders = [];

        if (!empty($order_ids) && is_array($order_ids) === true) {
            foreach ($order_ids as $order_id) {
                $global_order_id = NULL;
                try {
                    $order_data = $this->getOrderDetails($order_id);
                    $getOrderRowData = $this->getOrderRowData($order_id)['order'];
                    $order = $order_data;
                    $order_edge = $order_data['data']['order'];
                    $weight = $this->calculateVariantWeights($order_edge['lineItems']['edges']);
                    $global_order_id = $order_edge['name'];

                    if (!isset($pickup_account) || empty($pickup_account)) {
                        throw new Exception("Couldn't find pickup account, Please select it from Configuration and Try again.!!");
                    }

                    $label_data = Label::where('shop', $this->shop->name)->where('order_id', $order_id)->first();

                    if (!empty($label_data)) {
                        throw new Exception("Label already generated");
                    }

                    if (isset($order) && !empty($order)) {
                        $order_id_parts = explode('/', $order_edge['id']);
                        $order_info = [
                            "id" => end($order_id_parts),
                            "name" => $order_edge['name'],
                            "currencyCode" => $order_edge['currencyCode'],
                            "createdAt" => $order_edge['createdAt'],
                            "clientIp" => $order_edge['clientIp'],
                            "currentTotalWeight" => $order_edge['currentTotalWeight'],
                            "billingAddress" => $order_edge['billingAddress'],
                            "shippingAddress" => $order_edge['shippingAddress'],
                            "customer" => $order_edge['customer'],
                            "weight" => $weight
                        ];

                        $product_info = [];
                        if (isset($order_edge['lineItems']['edges'])) {
                            $products = $order_edge['lineItems']['edges'];
                            foreach ($products as $product) {
                                $product_id = explode('/', $product['node']['id']);
                                $product_info[] = [
                                    'id' => end($product_id),
                                    'name' => $product['node']['name'],
                                    'title' => $product['node']['title']
                                ];
                            }
                        }

                        $order_info['products'] = isset($product_info) && !empty($product_info) ? $product_info : [];
                        $orders[] = $order_info;
                    }

                    if (isset($order_info) && !empty($order_info)) {
                        $order = $order_info;
                        $shipment_id = $prefix . substr($order['name'], 1);
                        $package_description = $order['products'][0]['name'] ?? 'Order ' . $order['name'];
                        $remarks = $order['products'][0]['name'] ?? 'Order ' . $order['name'];

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
                            'totalWeight' => isset($order['weight']) ? (int) $order['weight'] : 0,
                            'totalWeightUOM' => 'G',
                            'productCode' => $product_code,
                            'codValue' => $getOrderRowData['payment_gateway_names'][0] == 'Cash on Delivery (COD)' ? (float)$getOrderRowData['total_price'] : null,
                            'insuranceValue' => !empty($shipment_value_protection) ? floatval($shipment_value_protection) : null,
                            'currency' => $currency,
                            'remarks' => isset($remarks) && !empty($remarks) ? $remarks : null,
                            'isMult' => $multi_pieces_shipment
                        ];

                        // Add value added services if selected
                        $selected_services = $request->services ?? [];
                        if (!empty($selected_services)) {
                            $value_added_services = [];
                            foreach ($selected_services as $service) {
                                $value_added_services[] = ['vasCode' => $service];
                            }
                            $shipment_item['valueAddedServices'] = [
                                'valueAddedService' => $value_added_services
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
                                $label = $labels_response[0];
                                $response_status = $label['responseStatus'];

                                if (isset($response_status['message']) && $response_status['message'] == 'SUCCESS') {
                                    $shipment_id = $label['shipmentID'];
                                    $delivery_confirmation_no = $label['deliveryConfirmationNo'];
                                    $content = $label['content'];

                                    if (isset($label['pieces']) && !empty($label['pieces']) && isset($label['pieces'][0]) && !empty($label['pieces'][0])) {
                                        $pieces = $label['pieces'][0];
                                        $delivery_confirmation_no = $pieces['deliveryConfirmationNo'];
                                        $content = $pieces['content'];
                                    }

                                    $label_format = isset($configuration['label_format']) && !empty($configuration['label_format']) ? $configuration['label_format'] : 'PNG';
                                    $label_format_ext = strtolower($label_format);
                                    $image = $shipment_id . "." . $label_format_ext;

                                    if ($label_format == "PNG" || $label_format == "ZPL") {
                                        Storage::disk('local')->put($this->labels_dir . '/' . $image, base64_decode($content));
                                    } elseif ($label_format == "PDF") {
                                        $pdf_decoded = base64_decode($content);
                                        $pdf_name = Storage::disk('local')->path($this->labels_dir . '/' . $image);
                                        $pdf = fopen($pdf_name, 'w');
                                        fwrite($pdf, $pdf_decoded);
                                        fclose($pdf);
                                    }

                                    $generated_labels = [[
                                        "label" => $image,
                                        "ppod" => '',
                                        "delivery_confirmation_no" => $delivery_confirmation_no,
                                        "extension" => $label_format_ext
                                    ]];

                                    Storage::disk('local')->put($this->label_json_dir . "/" . $shipment_id . ".json", json_encode($request_data));

                                    $is_updated = Label::where([
                                        'shop' => $this->shop->name,
                                        'order_id' => $order['id']
                                    ])->update([
                                        'shipment_id' => $shipment_id,
                                        'delivery_confirmation_no' => $delivery_confirmation_no,
                                        'content' => $content,
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

                                    // Collect label file for PDF merging
                                    $label_path = Storage::disk('local')->path($this->labels_dir . '/' . $image);
                                    if (file_exists($label_path)) {
                                        $labels[] = [
                                            "file" => $label_path,
                                            "name" => $image
                                        ];
                                    }

                                    array_push($bulk_response, [
                                        'order_id' => $global_order_id,
                                        'message' => "Label has been generated successfully",
                                        'status' => 'success'
                                    ]);
                                } else {
                                    throw new Exception($response_status['messageDetails'][0]['messageDetail']);
                                }
                            }
                        } else {
                            if (isset($dhl_label_response['labelResponse']['bd']['responseStatus'])) {
                                $bd_response = $dhl_label_response['labelResponse']['bd']['responseStatus'];
                                if ($bd_response['code'] != '200' || $bd_response['message'] != 'SUCCESS')
                                    throw new Exception($bd_response['messageDetails'][0]['messageDetail']);
                            }
                        }
                    }
                } catch (Exception $ex) {
                    array_push($bulk_response, [
                        'order_id' => $global_order_id,
                        'message' => $ex->getMessage(),
                        'status' => 'error'
                    ]);
                }
            }
        }

        // Step 1: Convert images to PDFs and collect all PDFs
        $pdfFiles = [];
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
                $pdfFiles[] = $file_path;
            }
        }

        // Step 2: Merge all PDFs into a single PDF
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
            return view('shippingLabel.bulk-create', ['bulk_response' => $bulk_response, 'downloadURL' => '', 'error' => 'Failed to merge PDFs']);
        }

        // Step 3: Create ZIP file containing the merged PDF
        $downloadURL = "";
        if (!empty($pdfFiles)) {
            $zip = new ZipArchive();
            $zipdir = Storage::disk('local')->path($this->zip_label_dir);

            if (!file_exists($zipdir)) {
                mkdir($zipdir, 0777, true);
            }

            $filename = $zipdir . "/bulkcreatelabels.zip";

            if (file_exists($filename)) {
                @unlink($filename);
            }

            if ($zip->open($filename, ZipArchive::CREATE)) {
                if (file_exists($merged_pdf_path)) {
                    $zip->addFile($merged_pdf_path, "DHLBulkLabels.pdf");
                }
                $zip->close();

                $downloadURL = asset('storage/app/' . $this->zip_label_dir . "/bulkcreatelabels.zip");
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

        // Rebuild orders array for the view after processing
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

        // Prepare data for the view
        $pickup_accounts = $this->pickupAccounts;
        $configArray = $this->configuration;
        
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
                'accountName' => $pickup_account['company'],
                'accountNo' => $pickup_account['number'],
            ];
        }

        $value_added_services = [
            ['code' => 'INS', 'label' => 'Additional insurance'],
            ['code' => 'OBOX', 'label' => 'Open Box'],
            ['code' => 'PPOD', 'label' => 'Paper Proof of Delivery'],
            ['code' => 'COD', 'label' => 'Cash on Delivery']
        ];

        // Get generated label IDs for successful orders
        $generatedLabelIds = [];
        if (!empty($bulk_response)) {
            foreach ($bulk_response as $response) {
                if (($response['status'] ?? '') === 'success') {
                    $orderName = $response['order_id']; // This is actually the order name
                    // Find the corresponding order ID from the orders array
                    $orderId = null;
                    foreach ($orders as $order) {
                        if ($order['name'] === $orderName) {
                            $orderId = $order['id'];
                            break;
                        }
                    }
                    
                    if ($orderId) {
                        $label = Label::where('shop', $this->shop->name)
                            ->where('order_id', $orderId)
                            ->first();
                        if ($label) {
                            $generatedLabelIds[$orderName] = $label->id;
                        }
                    }
                }
            }
        }

        // Store in session for persistence across page refreshes
        session([
            'bulk_response' => $bulk_response,
            'generatedLabelIds' => $generatedLabelIds,
            'labelsGenerated' => !empty($bulk_response),
            'downloadURL' => $downloadURL,
        ]);

        return view('shippingLabel.bulk-create', [
            'orders' => $orders,
            'pickup_accounts' => $pickup_accounts,
            'configuration' => $configArray,
            'shipping_products' => $shipping_products,
            'value_added_services' => $value_added_services,
            'bulk_response' => $bulk_response,
            'downloadURL' => $downloadURL,
            'labels_dir' => $this->labels_dir,
            'prefix' => $prefix,
            'labelsGenerated' => !empty($bulk_response),
            'generatedLabelIds' => $generatedLabelIds,
        ])->with('activePickup', (object)$pickup_account);
    }

    public function downloadLabels(Request $request)
    {
        $order_ids = $request->ids ?? [];
        $label_ids = $request->labelIds ?? [];
        $labels = [];
        $pdfFiles = [];

        if (empty($order_ids) && empty($label_ids)) {
            return response()->json(['error' => 'No order IDs or label IDs provided'], 400);
        }

        // Collect all label files
        if (!empty($label_ids) && is_array($label_ids)) {
            // Use specific label IDs if provided
            foreach ($label_ids as $label_id) {
                $label_data = Label::where('shop', $this->shop->name)->where('id', $label_id)->first();
                if ($label_data) {
                    $label_data = $label_data->toArray();
                    $generated_labels = isset($label_data['generated_labels']) && !empty($label_data['generated_labels']) 
                        ? json_decode($label_data['generated_labels'], true) 
                        : [];

                    if (!empty($generated_labels)) {
                        foreach ($generated_labels as $generated_label) {
                            $label_path = Storage::disk('local')->path($this->labels_dir . "/" . $generated_label['label']);
                            if (file_exists($label_path)) {
                                $labels[] = [
                                    "file" => $label_path,
                                    "name" => $generated_label['label']
                                ];
                            }
                            
                            // Include PPOD if available
                            if (isset($generated_label['ppod']) && !empty($generated_label['ppod'])) {
                                $ppod_path = Storage::disk('local')->path($this->labels_dir . "/" . $generated_label['ppod']);
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
        } elseif (!empty($order_ids) && is_array($order_ids)) {
            // Fallback to order IDs
            foreach ($order_ids as $order_id) {
                $label_data = Label::where('shop', $this->shop->name)->where('order_id', $order_id)->first();
                if ($label_data) {
                    $label_data = $label_data->toArray();
                    $generated_labels = isset($label_data['generated_labels']) && !empty($label_data['generated_labels']) 
                        ? json_decode($label_data['generated_labels'], true) 
                        : [];

                    if (!empty($generated_labels)) {
                        foreach ($generated_labels as $generated_label) {
                            $label_path = Storage::disk('local')->path($this->labels_dir . "/" . $generated_label['label']);
                            if (file_exists($label_path)) {
                                $labels[] = [
                                    "file" => $label_path,
                                    "name" => $generated_label['label']
                                ];
                            }
                            
                            // Include PPOD if available
                            if (isset($generated_label['ppod']) && !empty($generated_label['ppod'])) {
                                $ppod_path = Storage::disk('local')->path($this->labels_dir . "/" . $generated_label['ppod']);
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

        if (empty($labels)) {
            return response()->json(['error' => 'No labels found for the specified orders. Please ensure labels have been generated first.'], 404);
        }

        // Convert images to PDFs and collect all PDFs
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
                $pdfFiles[] = $file_path;
            }
        }

        // Merge all PDFs into a single PDF
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
            return response()->json(['error' => 'Failed to merge PDFs'], 500);
        }

        // Create ZIP file containing the merged PDF
        $zip = new ZipArchive();
        $zipdir = Storage::disk('local')->path($this->zip_label_dir);

        if (!file_exists($zipdir)) {
            mkdir($zipdir, 0777, true);
        }

        $filename = $zipdir . "/bulkdownloadlabels.zip";

        if (file_exists($filename)) {
            @unlink($filename);
        }

        if ($zip->open($filename, ZipArchive::CREATE)) {
            if (file_exists($merged_pdf_path)) {
                $zip->addFile($merged_pdf_path, "DHLBulkLabels.pdf");
            }
            $zip->close();

            $downloadURL = asset('storage/app/' . $this->zip_label_dir . "/bulkdownloadlabels.zip");
            
            // Clean up temporary PDFs
            foreach ($pdfFiles as $file) {
                if (file_exists($file) && strpos($file, $temp_dir) !== false) {
                    @unlink($file);
                }
            }
            if (file_exists($merged_pdf_path)) {
                @unlink($merged_pdf_path);
            }

            return response()->json(['downloadURL' => $downloadURL]);
        }

        return response()->json(['error' => 'Failed to create ZIP file'], 500);
    }

    public function printLabels(Request $request)
    {
        $order_ids = $request->ids ?? [];
        $label_ids = $request->labelIds ?? [];
        $labels = [];
        $pdfFiles = [];

        if (empty($order_ids) && empty($label_ids)) {
            return response()->json(['error' => 'No order IDs or label IDs provided'], 400);
        }

        // Collect all label files
        if (!empty($label_ids) && is_array($label_ids)) {
            // Use specific label IDs if provided
            foreach ($label_ids as $label_id) {
                $label_data = Label::where('shop', $this->shop->name)->where('id', $label_id)->first();
                if ($label_data) {
                    $label_data = $label_data->toArray();
                    $generated_labels = isset($label_data['generated_labels']) && !empty($label_data['generated_labels']) 
                        ? json_decode($label_data['generated_labels'], true) 
                        : [];

                    if (!empty($generated_labels)) {
                        foreach ($generated_labels as $generated_label) {
                            $label_path = Storage::disk('local')->path($this->labels_dir . "/" . $generated_label['label']);
                            if (file_exists($label_path)) {
                                $labels[] = [
                                    "file" => $label_path,
                                    "name" => $generated_label['label']
                                ];
                            }
                            
                            // Include PPOD if available
                            if (isset($generated_label['ppod']) && !empty($generated_label['ppod'])) {
                                $ppod_path = Storage::disk('local')->path($this->labels_dir . "/" . $generated_label['ppod']);
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
        } elseif (!empty($order_ids) && is_array($order_ids)) {
            // Fallback to order IDs
            foreach ($order_ids as $order_id) {
                $label_data = Label::where('shop', $this->shop->name)->where('order_id', $order_id)->first();
                if ($label_data) {
                    $label_data = $label_data->toArray();
                    $generated_labels = isset($label_data['generated_labels']) && !empty($label_data['generated_labels']) 
                        ? json_decode($label_data['generated_labels'], true) 
                        : [];

                    if (!empty($generated_labels)) {
                        foreach ($generated_labels as $generated_label) {
                            $label_path = Storage::disk('local')->path($this->labels_dir . "/" . $generated_label['label']);
                            if (file_exists($label_path)) {
                                $labels[] = [
                                    "file" => $label_path,
                                    "name" => $generated_label['label']
                                ];
                            }
                            
                            // Include PPOD if available
                            if (isset($generated_label['ppod']) && !empty($generated_label['ppod'])) {
                                $ppod_path = Storage::disk('local')->path($this->labels_dir . "/" . $generated_label['ppod']);
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

        if (empty($labels)) {
            return response()->json(['error' => 'No labels found for the specified orders. Please ensure labels have been generated first.'], 404);
        }

        // Convert images to PDFs and collect all PDFs
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
                $pdfFiles[] = $file_path;
            }
        }

        // Merge all PDFs into a single PDF
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
            return response()->json(['error' => 'Failed to merge PDFs'], 500);
        }

        // Create ZIP file containing the merged PDF
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

            $printURL = asset('storage/app/' . $this->zip_label_dir . "/bulkprintlabels.zip");
            
            // Clean up temporary PDFs
            foreach ($pdfFiles as $file) {
                if (file_exists($file) && strpos($file, $temp_dir) !== false) {
                    @unlink($file);
                }
            }
            if (file_exists($merged_pdf_path)) {
                @unlink($merged_pdf_path);
            }

            return response()->json(['printURL' => $printURL]);
        }

        return response()->json(['error' => 'Failed to create ZIP file'], 500);
    }

    public function downloadLabelDirect($label_id)
    {
        $label_data = Label::where('shop', $this->shop->name)->where('id', $label_id)->first();
        
        if (!$label_data) {
            return response()->json(['error' => 'Label not found'], 404);
        }

        $generated_labels = json_decode($label_data->generated_labels, true) ?? [];
        
        if (empty($generated_labels)) {
            return response()->json(['error' => 'No generated labels found'], 404);
        }

        $label = $generated_labels[0]; // Get the first label
        $label_path = Storage::disk('local')->path($this->labels_dir . "/" . $label['label']);
        
        if (!file_exists($label_path)) {
            return response()->json(['error' => 'Label file not found'], 404);
        }

        // Return file content directly instead of download
        $file_content = file_get_contents($label_path);
        $mime_type = mime_content_type($label_path);
        
        return response($file_content)
            ->header('Content-Type', $mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $label['label'] . '"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    public function printLabelDirect($label_id)
    {
        $label_data = Label::where('shop', $this->shop->name)->where('id', $label_id)->first();
        
        if (!$label_data) {
            return response()->json(['error' => 'Label not found'], 404);
        }

        $generated_labels = json_decode($label_data->generated_labels, true) ?? [];
        
        if (empty($generated_labels)) {
            return response()->json(['error' => 'No generated labels found'], 404);
        }

        $label = $generated_labels[0]; // Get the first label
        $label_path = Storage::disk('local')->path($this->labels_dir . "/" . $label['label']);
        
        if (!file_exists($label_path)) {
            return response()->json(['error' => 'Label file not found'], 404);
        }

        // Return file content directly for printing
        $file_content = file_get_contents($label_path);
        $mime_type = mime_content_type($label_path);
        
        return response($file_content)
            ->header('Content-Type', $mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $label['label'] . '"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
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

<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\PickupController;
use App\Http\Controllers\ShippinglabelController;
use Osiset\ShopifyApp\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\CarrierController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');

    dd("Cache is cleared");
});

Route::get('/test', function () {
    echo "RUNNING TO Test PROJECT...";
});

Route::get('/migrate', function () {
    $exitCode = Artisan::call('migrate', [
        '--force' => true,
    ]);
});

/**
 * Mandatory webhooks
 */
Route::middleware(['shopify_custom'])->group(function () {
    Route::any('customers/data_request', [DashboardController::class, 'customersDataRequest'])->name('customers.data_request');
    Route::any('customers/redact', [DashboardController::class, 'customersRedact'])->name('customers.redact');
    Route::any('shop/redact', [DashboardController::class, 'shopRedact'])->name('shop.redact');
});

// Dummy rate response route
Route::post('/rates-callback', [CarrierController::class, 'ratesCallback'])->name('carrier.rates');

Route::middleware(['verify.shopify'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    // Dashboard Routes
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Configuration Routes
    Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
    Route::post('configuration/add', [ConfigurationController::class, 'add'])->name('configuration.add');
    Route::post('configuration/auth', [ConfigurationController::class, 'auth'])->name('configuration.auth');

    // Pickup Routes
    Route::get('pickup-accounts', [PickupController::class, 'index'])->name('pickup.index');
    Route::get('add-pickup', [PickupController::class, 'show_pickup'])->name('pickup.showpickup');
    Route::post('pickup/add', [PickupController::class, 'add'])->name('pickup.add');
    Route::get('pickup-accounts/{num}', [PickupController::class, 'edit_pickup'])->name('pickup.editpickup');
    Route::post('pickup/edit', [PickupController::class, 'edit'])->name('pickup.edit');
    Route::post('pickup-accounts/list', [PickupController::class, 'list'])->name('pickup.list');
    Route::post('pickup-accounts/default', [PickupController::class, 'default'])->name('pickup.default');
    Route::post('pickup-accounts/status', [PickupController::class, 'status'])->name('pickup.status');
    Route::post('pickup-accounts/delete', [PickupController::class, 'delete'])->name('pickup.delete');

    // Shipping Label Routes
    Route::get('shipping-label/list', [ShippinglabelController::class, 'index'])->name('shippinglabel.index');
    Route::get('shipping-label/create', [ShippinglabelController::class, 'create'])->name('shippinglabel.create');
    Route::post('shipping-label/store', [ShippinglabelController::class, 'store'])->name('shippinglabel.store');
    Route::get('shipping-label/view/{order_id}', [ShippinglabelController::class, 'view'])->name('shippinglabel.view');
    Route::get('shipping-label/edit/{order_id}', [ShippinglabelController::class, 'edit'])->name('shippinglabel.edit');
    Route::post('shipping-label/update', [ShippinglabelController::class, 'update'])->name('shippinglabel.update');
    Route::get('shipping-label/delete', [ShippinglabelController::class, 'delete'])->name('shippinglabel.delete');
    
    Route::match(['get', 'post'], 'shipping-label/bulk-create', [ShippinglabelController::class, 'bulkCreate'])
    ->name('shippinglabel.bulk-create');
    
    // Download and Print Labels
Route::post('shipping-label/download-labels', [ShippinglabelController::class, 'downloadLabels'])->name('shippinglabel.download-labels');
Route::post('shipping-label/print-labels', [ShippinglabelController::class, 'printLabels'])->name('shippinglabel.print-labels');
Route::get('shipping-label/download-direct/{label_id}', [ShippinglabelController::class, 'downloadLabelDirect'])->name('shippinglabel.download-direct');
Route::get('shipping-label/print-direct/{label_id}', [ShippinglabelController::class, 'printLabelDirect'])->name('shippinglabel.print-direct');
    
    // Legacy Bulk Print for Already-Generated Labels
    Route::get('shipping-label/bulk-print', [ShippinglabelController::class, 'bulkPrint'])->name('shippingLabel.bulkPrint');

    // Webhook Route
    Route::post('/webhooks', function () {
        return response()->json(['message' => 'Webhook received successfully']);
    });
});

// Dedicated POST endpoint to avoid re-auth redirect when generating labels
Route::match(['get', 'post'],'shipping-label/bulk-create/submit', [ShippinglabelController::class, 'bulkCreate'])
    ->name('shippinglabel.bulk-create.submit');


    @extends('layouts.master')

@section('moduleName')
    Create bulk labels
@endsection

@section('content')
    <div class="Polaris-Page">
        <div class="Polaris-Page-Header">
            <div class="Polaris-Page-Header__MainContent">
                <div class="Polaris-Page-Header__TitleActionMenuWrapper">
                    <div>
                        <div class="Polaris-Header-Title__TitleAndSubtitleWrapper">
                            <div class="Polaris-Header-Title">
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Create bulk labels</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (session('error') || isset($error))
            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Banner Polaris-Banner--statusCritical Polaris-Banner--withinPage">
                        <div class="Polaris-Banner__ContentWrapper">
                            <div class="Polaris-Banner__Heading"><p class="Polaris-Heading">Error</p></div>
                            <div class="Polaris-Banner__Content"><p>{{ session('error') ?? $error }}</p></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Show warning if no fulfillments -->
        @if (!empty($noFulfillments))
            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Banner Polaris-Banner--statusWarning Polaris-Banner--withinPage">
                        <div class="Polaris-Banner__ContentWrapper">
                            <div class="Polaris-Banner__Heading"><p class="Polaris-Heading">No fulfillments available</p></div>
                            <div class="Polaris-Banner__Content">
                                <p>
                                    We could not find any fulfilled orders in your selection.  
                                    <br><br>
                                    <a href="https://docs.dhlecommerce.app/docs/label-creation/bulk-label-creation/createMultipleFulfillments" 
                                    target="_blank" 
                                    style="color:#005bd3; text-decoration:underline; font-weight:500;">
                                        Click here to check out our documentation
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @php
            // Filter only errors from bulk_response
            $errors = array_filter($bulk_response ?? [], fn($r) => ($r['status'] ?? '') === 'error');
            $hasErrors = !empty($errors);
            $successCount = count(array_filter($bulk_response ?? [], fn($r) => ($r['status'] ?? '') === 'success'));
            $hasSuccess = $successCount > 0;
        @endphp

        {{-- Success Banner --}}
        @if ($hasSuccess)
            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Banner Polaris-Banner--statusSuccess Polaris-Banner--withinPage">
                        <div class="Polaris-Banner__ContentWrapper">
                            <div class="Polaris-Banner__Heading"><p class="Polaris-Heading">Success</p></div>
                            <div class="Polaris-Banner__Content"><p>{{ $successCount }} label(s) generated successfully</p></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Error Banner --}}
        @if ($hasErrors)
            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Banner Polaris-Banner--statusCritical Polaris-Banner--withinPage">
                        <div class="Polaris-Banner__ContentWrapper">
                            <div class="Polaris-Banner__Heading"><p class="Polaris-Heading">Error</p></div>
                            <div class="Polaris-Banner__Content">
                                <p>There were some errors when creating the following labels:</p>
                                <ul style="list-style-type: disc; margin-left: 20px; margin-top: 8px;">
                                    @foreach ($errors as $error)
                                        <li>{{ $error['order_id'] }}: {{ $error['message'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form id="bulkForm" action="{{ route('shippinglabel.bulk-create.submit', ['shop' => request('shop')]) }}" method="POST">
            @csrf
            <input type="hidden" name="shop" value="{{ request('shop') }}">
            @if (!empty($orders))
                @foreach ($orders as $o)
                    <input type="hidden" name="ids[]" value="{{ $o['id'] }}">
                @endforeach
            @endif

            <div class="Polaris-Layout">
                <!-- Left column always shows -->
                <div class="Polaris-Layout__Section Polaris-Layout__Section--oneThird">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Header"><h2 class="Polaris-Heading">Shipping product</h2></div>
                        <div class="Polaris-Card__Section">
                            <!-- <div class="Polaris-TextField" style="margin-bottom:12px;">
                                <input class="Polaris-TextField__Input" type="text" placeholder="Search products" disabled>
                            </div> -->
                            <div id="productList">
                                @if(!empty($activePickup) && !empty($shipping_products))
                                    @foreach($shipping_products as $product)
                                        <div class="Polaris-ResourceItem"
                                            style="border-radius:4px;padding:8px 12px;margin-bottom:4px;display:flex;align-items:center;gap:10px;cursor:pointer;"
                                            title="{{ $activePickup->company }} - #{{ $activePickup->number }}">
                                            <span class="Polaris-Badge"
                                                style="background-color: #4caf50;color:white;font-weight:600;">
                                                ACTIVE
                                            </span>
                                            <div style="flex:1;">
                                                <div style="font-weight:600;">
                                                    {{ $product['name'] }}
                                                </div>
                                                <div style="font-size:12px;color:#637381;">
                                                    {{ $activePickup->company }} - #{{ $activePickup->number }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div style="color:#999;font-size:14px;">No active pickup account or product code found</div>
                                @endif
                            </div>
                            <!-- <div style="margin-top:10px;">
                                <a href="javascript:void(0)">Why is my shipping product not here?</a>
                            </div> -->
                        </div>
                    </div>
                </div>

                <!-- Right: Available options + handover + button -->
                @if (empty($noFulfillments) && !empty($orders))
                    <!-- Right column only if fulfillments exist and we have orders -->
                    <div class="Polaris-Layout__Section Polaris-Layout__Section--twoThirds">
                        <div class="Polaris-Card">
                            <div class="Polaris-Card__Header">
                                <div class="Polaris-Stack Polaris-Stack--alignmentCenter">
                                    <div class="Polaris-Stack__Item"><span class="Polaris-Badge">ECS</span></div>
                                    <div class="Polaris-Stack__Item">
                                        <strong id="shippingSummary">
                                            {{ $shipping_products[0]['accountName'] ?? '' }} - #{{ $shipping_products[0]['accountNo'] ?? '' }} - {{ $shipping_products[0]['name'] ?? '' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div id="selectedOptionsDisplay" style="font-weight:bold;color:#101828;"></div>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div style="margin-bottom:6px;color:#637381;font-size:13px;">Handover</div>
                                <div id="handoverDisplay" style="font-weight:bold;">Pickup</div>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div class="Polaris-FormLayout">
                                    <div class="Polaris-FormLayout__Item">
                                        @foreach ($value_added_services as $vas)
                                            <label style="display:block;margin-bottom:8px;">
                                                <input type="checkbox" class="availableOption" name="services[]" value="{{ $vas['code'] }}"> {{ $vas['label'] }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div class="Polaris-FormLayout__Item">
                                    <div class="Polaris-Labelled__LabelWrapper">
                                        <div class="Polaris-Label">
                                            <label for="handoverMethod" class="Polaris-Label__Text">Handover Method</label>
                                        </div>
                                    </div>
                                    <div class="Polaris-Select">
                                        <select name="handoverMethod" id="handoverMethod" class="Polaris-Select__Input">
                                            <option value="Pickup" selected>Pickup</option>
                                            <option value="Drop-off">Drop-off</option>
                                        </select>
                                        <div class="Polaris-Select__Content">
                                            <span class="Polaris-Select__SelectedOption" id="handoverSelected">Pickup</span>
                                            <span class="Polaris-Select__Icon">...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @php
                $errors = array_filter($bulk_response ?? [], fn($r) => ($r['status'] ?? '') === 'error');
                $hasErrors = !empty($errors);
                $successCount = count(array_filter($bulk_response ?? [], fn($r) => ($r['status'] ?? '') === 'success'));
                $hasSuccess = $successCount > 0;
            @endphp

            <div class="Polaris-Card__Section" style="text-align:right;">
                @if (!empty($noFulfillments))
                    <span class="Polaris-TextStyle--variationSubdued">No available labels to create</span>
                @elseif ($hasErrors && $successCount === 0)
                    {{-- Only errors, no successful labels --}}
                    <span class="Polaris-TextStyle--variationSubdued">No available labels to create</span>
                    <button type="submit" class="Polaris-Button Polaris-Button--primary" id="createLabelsBtn" disabled style="opacity:0.5;">
                        Create labels
                    </button>
                @elseif ($hasSuccess)
                    {{-- Some labels generated successfully --}}
                    <span class="Polaris-TextStyle--variationSubdued">{{ $successCount }} label(s) generated successfully</span>
                @else
                    {{-- No labels generated yet --}}
                    <button type="submit" class="Polaris-Button Polaris-Button--primary" id="createLabelsBtn">
                        <span class="Polaris-Button__Content">
                            <span class="Polaris-Button__Text">
                                Create <span id="labelCount">{{ count($orders) }}</span> labels
                            </span>
                        </span>
                    </button>
                @endif
            </div>


            <div class="Polaris-Layout" style="margin-top:16px;">
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Header"><h2 class="Polaris-Heading">Orders</h2></div>
                        <div class="Polaris-Card__Section">
                            <div class="Polaris-TextField" style="margin-bottom:12px;">
                                <input id="orderSearch" class="Polaris-TextField__Input" type="text" placeholder="Find fulfillment by order name, location...">
                            </div>
                            @if ($hasSuccess)
                                <div style="margin-bottom:12px; text-align:right;">
                                    <button type="button" 
                                            class="Polaris-Button Polaris-Button--primary"
                                            onclick="downloadAllLabels()">
                                        <span class="Polaris-Button__Content">
                                            <span class="Polaris-Button__Text">Download All Labels</span>
                                        </span>
                                    </button>

                                    <button type="button" 
                                            class="Polaris-Button"
                                            onclick="printAllLabels()">
                                        <span class="Polaris-Button__Content">
                                            <span class="Polaris-Button__Text">Print All Labels</span>
                                        </span>
                                    </button>
                                </div>
                            @endif
                            <div class="Polaris-DataTable">
                                <table class="Polaris-DataTable__Table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Creation date</th>
                                            <th>Fulfillment location</th>
                                            <th>Destination</th>
                                            <th>Weight</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ordersTableBody">
                                        @forelse ($orders as $index => $o)
                                            <tr data-id="{{ $o['id'] }}">
                                                <td>{{ $o['name'] }}</td>
                                                <td>{{ date('d-m-Y', strtotime($o['createdAt'])) }}</td>
                                                <td>{{ $o['shippingAddress']['address1'] ?? '-' }}</td>
                                                <td>{{ $o['destination'] ?? '-' }}</td>
                                                <td>{{ number_format((float)$o['weight'], 0) }} g</td>
                                                <td style="text-align:right;">
                                                    @php
                                                        $orderResponse = collect($bulk_response ?? [])->firstWhere('order_id', $o['name']);
                                                        $hasLabel = $orderResponse && ($orderResponse['status'] ?? '') === 'success';
                                                        $labelId = $hasLabel ? ($generatedLabelIds[$o['name']] ?? null) : null;
                                                    @endphp
                                                    @if ($hasLabel && $labelId)
                                                        <button type="button" 
                                                                class="Polaris-Button"
                                                                onclick="downloadSingleLabel('{{ $o['id'] }}', '{{ $labelId }}')">
                                                            <span class="Polaris-Button__Content">
                                                                <span class="Polaris-Button__Text">Download</span>
                                                            </span>
                                                        </button>
                                                        <button type="button" 
                                                                class="Polaris-Button"
                                                                onclick="printSingleLabel('{{ $o['id'] }}', '{{ $labelId }}')">
                                                            <span class="Polaris-Button__Content">
                                                                <span class="Polaris-Button__Text">Print</span>
                                                            </span>
                                                        </button>
                                                    @else
                                                        <button type="button" class="Polaris-Button Polaris-Button--destructive"
                                                                title="Remove fulfillment from the list" onclick="removeRow('{{ $o['id'] }}')">
                                                            <span class="Polaris-Button__Content">
                                                                <span class="Polaris-Button__Text">🗑</span>
                                                            </span>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="no-items-row">
                                                <td colspan="6" style="text-align:center; color:#999; padding:16px;">
                                                    <strong>No items found</strong><br>
                                                    <span style="font-size:13px;">Try changing the filters or search term</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Ensure current visible rows are submitted as ids[] and prevent 404 due to missing IDs
        (function(){
            var form = document.getElementById('bulkForm');
            var submit = document.getElementById('createLabelsBtn');
            if(form && submit){
                form.addEventListener('submit', function(e){
                    // VAS is optional now; no validation here
                    // Require at least one order row
                    var rowsPresent = document.querySelectorAll('#ordersTableBody tr').length > 0;
                    if(!rowsPresent){
                        e.preventDefault();
                        alert('Please select at least one order.');
                        return false;
                    }
                    // Clear existing ids[]
                    var existing = form.querySelectorAll('input[name="ids[]"]');
                    existing.forEach(function(n){ n.parentNode.removeChild(n); });
                    // Add ids[] for each remaining row
                    var rows = document.querySelectorAll('#ordersTableBody tr');
                    rows.forEach(function(r){
                        if(r.style.display === 'none'){ return; }
                        var id = r.getAttribute('data-id');
                        if(id){
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = id;
                            form.appendChild(input);
                        }
                    });
                    // Disable button to avoid double submit
                    submit.disabled = true;
                });
            }
        })();

        function removeRow(id){
            // Remove the row from the table
            var row = document.querySelector('tr[data-id="'+id+'"]');
            if(row){ row.remove(); }

            // Remove hidden input
            var hidden = document.querySelector('input[type="hidden"][name="ids[]"][value="'+id+'"]');
            if(hidden){ hidden.remove(); }

            // Update label count
            var count = document.querySelectorAll('input[name="ids[]"]').length;
            var countEl = document.getElementById('labelCount');
            if(countEl) countEl.innerText = count;

            // Check if table is now empty
            var tbody = document.getElementById('ordersTableBody');
            var rowsLeft = tbody.querySelectorAll('tr[data-id]').length;

            if(rowsLeft === 0){
                tbody.innerHTML = `
                    <tr class="no-items-row">
                        <td colspan="6" style="text-align:center; color:#999; padding:20px;">
                            <strong>Empty search results</strong><br>
                            <span style="font-size:13px; display:block; margin-top:4px;">No Items found</span>
                            <span style="font-size:13px; display:block; margin-top:2px;">Try changing the filters or search term</span>
                        </td>
                    </tr>
                `;
            }
        }

        @if (!empty($noFulfillments))
            document.addEventListener("DOMContentLoaded", function() {
                const btn = document.getElementById("createLabelsBtn");
                if (btn) {
                    btn.addEventListener("click", function(e) {
                        e.preventDefault();
                        alert("No available labels to create. Please ensure orders are fulfilled first.");
                    });
                }
            });
        @endif

        function printLabel(url) {
            if (!url) return;
            try {
                const printWindow = window.open(url, '_blank');
                if (!printWindow) { window.location.href = url; return; }
                printWindow.onload = function() {
                    printWindow.focus();
                    printWindow.print();
                };
            } catch(e) { window.location.href = url; }
        }

        function downloadFile(url){
            if(!url) return;
            try{
                const a = document.createElement('a');
                a.href = url;
                a.download = '';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }catch(e){
                window.location.href = url;
            }
        }

        var search = document.getElementById('orderSearch');
        if(search){
            search.addEventListener('input', function(){
                var term = this.value.toLowerCase();
                var rows = document.querySelectorAll('#ordersTableBody tr');
                rows.forEach(function(r){
                    var text = r.innerText.toLowerCase();
                    r.style.display = text.indexOf(term) !== -1 ? '' : 'none';
                });
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            const handoverSelect = document.getElementById("handoverMethod");
            const handoverSelected = document.getElementById("handoverSelected");
            const handoverDisplay = document.getElementById("handoverDisplay");

            if(handoverSelect){
                handoverSelect.addEventListener("change", function () {
                    const selectedText = handoverSelect.options[handoverSelect.selectedIndex].text;
                    handoverSelected.textContent = selectedText;
                    handoverDisplay.textContent = selectedText;
                });
            }
        });

        function updateSelectedOptions() {
            const selectedDiv = document.getElementById('selectedOptionsDisplay');
            const checked = Array.from(document.querySelectorAll('.availableOption:checked'));
            selectedDiv.innerHTML = '';
            if (checked.length > 0) {
                selectedDiv.style.display = 'flex';
                selectedDiv.style.flexWrap = 'wrap';
                selectedDiv.style.gap = '6px';
                checked.forEach(c => {
                    const div = document.createElement('div');
                    div.textContent = c.nextSibling.textContent.trim();
                    div.style.padding = '4px 10px';
                    div.style.background = '#f4f6f8';
                    div.style.border = '1px solid #dfe3e8';
                    div.style.borderRadius = '12px';
                    div.style.fontSize = '13px';
                    div.style.whiteSpace = 'nowrap';
                    selectedDiv.appendChild(div);
                });
            }
        }

        document.querySelectorAll('.availableOption').forEach(function(cb){
            cb.addEventListener('change', updateSelectedOptions);
        });

        updateSelectedOptions();

        // Download and Print Functions
        function downloadAllLabels() {
            const orderIds = Array.from(document.querySelectorAll('input[name="ids[]"]')).map(input => input.value);
            
            // Disable button to prevent multiple clicks
            const downloadBtn = event.target;
            downloadBtn.disabled = true;
            downloadBtn.textContent = 'Preparing download...';
            
            fetch('{{ route("shippinglabel.download-labels", ["shop" => request("shop")]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ids: orderIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.downloadURL) {
                    // Use iframe to download without redirect
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = data.downloadURL;
                    document.body.appendChild(iframe);
                    
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 1000);
                } else {
                    alert('Error: ' + (data.error || 'Failed to download labels'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error downloading labels');
            })
            .finally(() => {
                // Re-enable button
                downloadBtn.disabled = false;
                downloadBtn.textContent = 'Download All Labels';
            });
        }

        function printAllLabels() {
            const orderIds = Array.from(document.querySelectorAll('input[name="ids[]"]')).map(input => input.value);
            
            // Disable button to prevent multiple clicks
            const printBtn = event.target;
            printBtn.disabled = true;
            printBtn.textContent = 'Preparing print...';
            
            fetch('{{ route("shippinglabel.print-labels", ["shop" => request("shop")]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ids: orderIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.printURL) {
                    // Use iframe to print without redirect
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = data.printURL;
                    document.body.appendChild(iframe);
                    
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 1000);
                } else {
                    alert('Error: ' + (data.error || 'Failed to prepare labels for printing'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error preparing labels for printing');
            })
            .finally(() => {
                // Re-enable button
                printBtn.disabled = false;
                printBtn.textContent = 'Print All Labels';
            });
        }

        function downloadSingleLabel(orderId, labelId) {
            // Use direct download for single labels without redirect
            const downloadUrl = '{{ route("shippinglabel.download-direct", ["shop" => request("shop"), "label_id" => ":labelId"]) }}'.replace(':labelId', labelId);
            
            // Create a hidden iframe to download without redirecting
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = downloadUrl;
            document.body.appendChild(iframe);
            
            // Remove iframe after download starts
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 1000);
        }

        function printSingleLabel(orderId, labelId) {
            // Use direct print for single labels without redirect
            const printUrl = '{{ route("shippinglabel.print-direct", ["shop" => request("shop"), "label_id" => ":labelId"]) }}'.replace(':labelId', labelId);
            
            // Create a hidden iframe to print without redirecting
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = printUrl;
            document.body.appendChild(iframe);
            
            // Remove iframe after print dialog opens
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 1000);
        }
    </script>
@endsection