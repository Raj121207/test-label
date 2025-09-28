@extends('layouts.master')

@section('moduleName')
    Create shipping label
@endsection

@section('content')
    <style type="text/css">
        .Polaris-Icon.multi-pieces-remove svg {
            fill: var(--p-action-critical);
        }

        .Polaris-Icon.multi-pieces-remove {
            width: 2.5rem;
            padding: 0.5rem;
            cursor: pointer;
        }

        .Polaris-Icon.multi-pieces-remove {
            display: flex;
            align-items: center;
            height: 100%;
            min-height: 3.6rem;
            margin-top: 0.3rem;
        }
    </style>
    @php
        $new_order_id = substr($order['name'], 1);
    @endphp
    <div class="Polaris-Page">
        <div class="Polaris-Page-Header">
            <div class="Polaris-Page-Header__MainContent">
                <div class="Polaris-Page-Header__TitleActionMenuWrapper">
                    <div>
                        <div class="Polaris-Header-Title__TitleAndSubtitleWrapper">
                            <div class="Polaris-Header-Title">
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">DHLeCS Shipping Label</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="Polaris-Layout">
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Layout">
                    <div class="Polaris-Layout__Section">
                        <div class="Polaris-Card">
                            <div class="Polaris-Card__Header">
                                <h2 class="Polaris-Heading">Create Shipping Label</h2>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div class="Polaris-FormLayout">
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Pickup-Account" class="Polaris-Label__Text">Pickup
                                                                Account</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Select">
                                                        <select id="Pickup-Account" class="Polaris-Select__Input">
                                                            <option value="">Please select</option>
                                                            @if (count($pickup_accounts) > 0)
                                                                @if (isset($pickup_accounts) && !empty($pickup_accounts))
                                                                    @foreach ($pickup_accounts as $pickup_account)
                                                                        <option
                                                                            {{ $pickup_account['is_default'] == 1 ? 'selected' : '' }}
                                                                            value="{{ $pickup_account['id'] }}"
                                                                            pickup-number="{{ $pickup_account['number'] }}">
                                                                            {{ $pickup_account['number'] }} -
                                                                            {{ $pickup_account['company'] }}</option>
                                                                    @endforeach
                                                                @endif
                                                            @endif

                                                        </select>
                                                        <div class="Polaris-Select__Content">
                                                            <span class="Polaris-Select__SelectedOption">Select Pickup
                                                                Account</span>
                                                            <span class="Polaris-Select__Icon">
                                                                <span class="Polaris-Icon">
                                                                    <span class="Polaris-VisuallyHidden">
                                                                    </span>
                                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                                        <path
                                                                            d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                        </path>
                                                                    </svg>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div class="Polaris-Select__Backdrop">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Shipment-ID" class="Polaris-Label__Text">Shipment
                                                                ID</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField Polaris-TextField--disabled">
                                                                <input disabled type="text" id="Shipment-ID"
                                                                    class="Polaris-TextField__Input"
                                                                    value="{{ $configuration['prefix'] . $new_order_id }}">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Product-Code" class="Polaris-Label__Text">Product
                                                                Code</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Select">
                                                        <select id="Product-Code" class="Polaris-Select__Input">
                                                            <option value="PDO"
                                                                {{ $configuration['product_code'] == 'PDO' ? 'selected' : '' }}>
                                                                Parcel Domestic</option>
                                                            <option value="PDR"
                                                                {{ $configuration['product_code'] == 'PDR' ? 'selected' : '' }}>
                                                                DHL Parcel Return</option>
                                                            <option value="PDE"
                                                                {{ $configuration['product_code'] == 'PDE' ? 'selected' : '' }}>
                                                                Parcel Domestic Expedited</option>
                                                            <option value="DDO"
                                                                {{ $configuration['product_code'] == 'DDO' ? 'selected' : '' }}>
                                                                Document Domestic</option>
                                                            <option value="SDP"
                                                                {{ $configuration['product_code'] == 'SDP' ? 'selected' : '' }}>
                                                                DHL Parcel Metro</option>
                                                        </select>
                                                        <div class="Polaris-Select__Content">
                                                            <span class="Polaris-Select__SelectedOption">Parcel
                                                                Domestic</span>
                                                            <span class="Polaris-Select__Icon">
                                                                <span class="Polaris-Icon">
                                                                    <span class="Polaris-VisuallyHidden">
                                                                    </span>
                                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                                        <path
                                                                            d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                        </path>
                                                                    </svg>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div class="Polaris-Select__Backdrop">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Layout">
                    <div class="Polaris-Layout__Section">
                        <div class="Polaris-Card">
                            <div class="Polaris-Card__Header">
                                <h2 class="Polaris-Heading">Value Added Services</h2>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div class="Polaris-FormLayout">
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Cash-On-Delivery" class="Polaris-Label__Text">Cash
                                                                on Delivery</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div class="Polaris-Connected__Item">
                                                            <div class="Polaris-Select">
                                                                <select id="Cash-On-Delivery-Option"
                                                                    class="Polaris-Select__Input" aria-invalid="false">
                                                                    <option <?php echo $payment_method[0] == 'manual' ? 'selected' : ''; ?> value="0" selected>NO
                                                                    </option>
                                                                    <option <?php echo $payment_method[0] == 'Cash on Delivery (COD)' ? 'selected' : ''; ?> value="1">YES</option>
                                                                </select>
                                                                <div class="Polaris-Select__Content" aria-hidden="true">
                                                                    <span class="Polaris-Select__SelectedOption">NO</span>
                                                                    <span class="Polaris-Select__Icon">
                                                                        <span class="Polaris-Icon">
                                                                            <svg viewBox="0 0 20 20"
                                                                                class="Polaris-Icon__Svg"
                                                                                focusable="false" aria-hidden="true">
                                                                                <path
                                                                                    d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                                </path>
                                                                            </svg>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                                <div class="Polaris-Select__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField Polaris-TextField--hasValue">
                                                                <input type="text" id="Cash-On-Delivery"
                                                                    class="Polaris-TextField__Input"
                                                                    value="{{ $payment_method[0] == 'Cash on Delivery (COD)' ? $total_price : '' }}">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Shipment-Value-Protection"
                                                                class="Polaris-Label__Text">Shipment Value
                                                                Protection</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div class="Polaris-Connected__Item">
                                                            <div class="Polaris-Select">
                                                                <select id="Shipment-Value-Protection-Option"
                                                                    class="Polaris-Select__Input" aria-invalid="false">
                                                                    <option value="0" selected>NO</option>
                                                                    <option value="1">YES</option>
                                                                </select>
                                                                <div class="Polaris-Select__Content" aria-hidden="true">
                                                                    <span class="Polaris-Select__SelectedOption">No</span>
                                                                    <span class="Polaris-Select__Icon">
                                                                        <span class="Polaris-Icon">
                                                                            <svg viewBox="0 0 20 20"
                                                                                class="Polaris-Icon__Svg"
                                                                                focusable="false" aria-hidden="true">
                                                                                <path
                                                                                    d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                                </path>
                                                                            </svg>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                                <div class="Polaris-Select__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField Polaris-TextField--hasValue">
                                                                <input type="text" id="Shipment-Value-Protection"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Paper-Proof-Delivery"
                                                                class="Polaris-Label__Text">Paper Proof of Delivery
                                                                (PPOD)</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div class="Polaris-Connected__Item">
                                                            <div class="Polaris-Select">
                                                                <select id="Paper-Proof-Delivery-Option"
                                                                    class="Polaris-Select__Input">
                                                                    <option value="0" selected>NO</option>
                                                                    <option value="1">YES</option>
                                                                </select>
                                                                <div class="Polaris-Select__Content" aria-hidden="true">
                                                                    <span class="Polaris-Select__SelectedOption">NO</span>
                                                                    <span class="Polaris-Select__Icon">
                                                                        <span class="Polaris-Icon">
                                                                            <svg viewBox="0 0 20 20"
                                                                                class="Polaris-Icon__Svg"
                                                                                focusable="false" aria-hidden="true">
                                                                                <path
                                                                                    d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                                </path>
                                                                            </svg>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                                <div class="Polaris-Select__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- <div class="Polaris-Connected__Item Polaris-Connected__Item--primary" style="display: none">
                                                                                                                                                                                                                                                                            <div class="Polaris-Select">
                                                                                                                                                                                                                                                                                <select id="Paper-Proof-Delivery-Action" class="Polaris-Select__Input">
                                                                                                                                                                                                                                                                                    <option value="">Please select</option>
                                                                                                                                                                                                                                                                                    <option value="1">Return All Documents</option>
                                                                                                                                                                                                                                                                                    <option value="2">Return as DHL Instruction Note on each Parcel</option>
                                                                                                                                                                                                                                                                                    <option value="3">Return as Customer Instruction Note on each Parcel</option>
                                                                                                                                                                                                                                                                                    <option value="4">Customized Instruction</option>
                                                                                                                                                                                                                                                                                </select>
                                                                                                                                                                                                                                                                                <div class="Polaris-Select__Content">
                                                                                                                                                                                                                                                                                    <span class="Polaris-Select__SelectedOption">Please Select</span>
                                                                                                                                                                                                                                                                                    <span class="Polaris-Select__Icon">
                                                                                                                                                                                                                                                                                        <span class="Polaris-Icon">
                                                                                                                                                                                                                                                                                            <span class="Polaris-VisuallyHidden">
                                                                                                                                                                                                                                                                                            </span>
                                                                                                                                                                                                                                                                                            <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                                                                                                                                                                                                                                                                <path d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                                                                                                                                                                                                                                                </path>
                                                                                                                                                                                                                                                                                            </svg>
                                                                                                                                                                                                                                                                                        </span>
                                                                                                                                                                                                                                                                                    </span>
                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                <div class="Polaris-Select__Backdrop">
                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                        </div> -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Open-Box" class="Polaris-Label__Text">Open
                                                                Box</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Select">
                                                        <select id="Open-Box" class="Polaris-Select__Input">
                                                            <option value="1">YES</option>
                                                            <option value="0" selected>NO</option>
                                                        </select>
                                                        <div class="Polaris-Select__Content">
                                                            <span class="Polaris-Select__SelectedOption">NO</span>
                                                            <span class="Polaris-Select__Icon">
                                                                <span class="Polaris-Icon">
                                                                    <span class="Polaris-VisuallyHidden">
                                                                    </span>
                                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                                        <path
                                                                            d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                        </path>
                                                                    </svg>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div class="Polaris-Select__Backdrop">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="Polaris-FormLayout__Items">
                                        <div class="Polaris-FormLayout__Item">
                                            <label class="Polaris-Choice" for="Multi-Pieces-Shipment">
                                                <span class="Polaris-Choice__Control">
                                                    <span class="Polaris-Checkbox">
                                                        <input id="Multi-Pieces-Shipment" type="checkbox"
                                                            class="Polaris-Checkbox__Input">
                                                        <span
                                                            class="Polaris-Checkbox__Backdrop Polaris-Checkbox--hover"></span>
                                                        <span class="Polaris-Checkbox__Icon">
                                                            <span class="Polaris-Icon">
                                                                <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg"
                                                                    focusable="false" aria-hidden="true">
                                                                    <path
                                                                        d="m8.315 13.859-3.182-3.417a.506.506 0 0 1 0-.684l.643-.683a.437.437 0 0 1 .642 0l2.22 2.393 4.942-5.327a.436.436 0 0 1 .643 0l.643.684a.504.504 0 0 1 0 .683l-5.91 6.35a.437.437 0 0 1-.642 0">
                                                                    </path>
                                                                </svg>
                                                            </span>
                                                        </span>
                                                    </span>
                                                </span>
                                                <span class="Polaris-Choice__Label">Multi Pieces Shipment</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="Polaris-Layout__Section Polaris-Layout__Section--secondary">
                        <div class="Polaris-Card">
                            <div class="Polaris-Card__Header">
                                <h2 class="Polaris-Heading">Consignee Address</h2>
                            </div>
                            <div class="Polaris-Card__Section">
                                <p>
                                    <span id="Customer-Name"></span>
                                    <br>
                                    <span id="Shipping-Address-phone"></span>
                                    <br>
                                    <span id="Customer-Address1"></span>
                                    <br>
                                    <span id="Customer-Address2"></span>
                                    <br>
                                    <span id="Customer-City"></span>
                                    <br>
                                    <span id="Customer-Province"></span>
                                    <br>
                                    <span id="Customer-Zip"></span>
                                    <br>
                                    <span id="Customer-CountryCode"></span>
                                    <br>
                                    <span id="Customer-Phone"></span>
                                    <br>
                                    <span id="Customer-Email"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="Polaris-Layout__Section" id="Multi-Pieces-Block-Section" style="display: none;">
                <div class="Polaris-Layout">
                    <div class="Polaris-Layout__Section">
                        <div class="Polaris-Card">
                            <div class="Polaris-Card__Section">
                                <div class="Polaris-FormLayout">
                                    <div class="Polaris-FormLayout__Items" id="Delivery-Option-Section"
                                        style="display: none;">
                                        <div class="Polaris-FormLayout__Item">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label class="Polaris-Label__Text">Delivery Option</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-Stack layout-stack" style="margin-bottom: 0;">
                                                        <div class="Polaris-Stack__Item">
                                                            <label class="Polaris-Choice" for="Complete-Delivery">
                                                                <span class="Polaris-Choice__Control">
                                                                    <span class="Polaris-RadioButton">
                                                                        <input id="Complete-Delivery"
                                                                            name="Delivery-Option" type="radio"
                                                                            class="Polaris-RadioButton__Input"
                                                                            value="C" checked>
                                                                        <span class="Polaris-RadioButton__Backdrop"></span>
                                                                    </span>
                                                                </span>
                                                                <span class="Polaris-Choice__Label">Complete
                                                                    Delivery</span>
                                                            </label>
                                                        </div>
                                                        <div class="Polaris-Stack__Item">
                                                            <label class="Polaris-Choice" for="Partial-Delivery">
                                                                <span class="Polaris-Choice__Control">
                                                                    <span class="Polaris-RadioButton">
                                                                        <input id="Partial-Delivery"
                                                                            name="Delivery-Option" type="radio"
                                                                            class="Polaris-RadioButton__Input"
                                                                            value="P">
                                                                        <span class="Polaris-RadioButton__Backdrop"></span>
                                                                    </span>
                                                                </span>
                                                                <span class="Polaris-Choice__Label">Partial Delivery</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="Polaris-FormLayout--condensed" id="Multi-Pieces-Block-Form"
                                        style="display: none;">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="Polaris-Labelled__LabelWrapper">
                                                    <div class="Polaris-Label"><label
                                                            class="Polaris-Label__Text"><small>Piece
                                                                Description</small></label></div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="Polaris-Labelled__LabelWrapper">
                                                    <div class="Polaris-Label"><label
                                                            class="Polaris-Label__Text"><small>Shipment
                                                                Weight(G)</small></label></div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="Polaris-Labelled__LabelWrapper">
                                                    <div class="Polaris-Label"><label
                                                            class="Polaris-Label__Text"><small>Billing Ref
                                                                1</small></label></div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="Polaris-Labelled__LabelWrapper">
                                                    <div class="Polaris-Label"><label
                                                            class="Polaris-Label__Text"><small>Billing Ref
                                                                2</small></label></div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="Polaris-Labelled__LabelWrapper">
                                                    <div class="Polaris-Label"><label
                                                            class="Polaris-Label__Text"><small>Shipment
                                                                Insurance</small></label></div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="Polaris-Labelled__LabelWrapper">
                                                    <div class="Polaris-Label"><label
                                                            class="Polaris-Label__Text"><small>Cash on
                                                                Delivery</small></label></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="multi-pieces-block-items">
                                            <div class="Polaris-FormLayout__Items multi-pieces-block">
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Piece-Description">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="number"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Shipment-Weight">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Billing-Ref1">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Billing-Ref2">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="number"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Shipment-Insurance">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="number"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Cash-On-Delivery">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="Polaris-Icon multi-pieces-remove">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                        class="Polaris-Icon__Svg">
                                                        <path
                                                            d="M8 3.994C8 2.893 8.895 2 10 2s2 .893 2 1.994h4c.552 0 1 .446 1 .997 0 .55-.448.997-1 .997H4c-.552 0-1-.447-1-.997s.448-.997 1-.997h4zM5 14.508V8h2v6.508a.5.5 0 00.5.498H9V8h2v7.006h1.5a.5.5 0 00.5-.498V8h2v6.508A2.496 2.496 0 0112.5 17h-5C6.12 17 5 15.884 5 14.508z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="multi-pieces-block-html" style="display: none;">
                                            <div class="Polaris-FormLayout__Items multi-pieces-block">
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Piece-Description">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="number"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Shipment-Weight">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Billing-Ref1">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Billing-Ref2">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="number"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Shipment-Insurance">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-FormLayout__Item">
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="number"
                                                                    class="Polaris-TextField__Input Polaris-Form__Input Cash-On-Delivery">
                                                                <div class="Polaris-TextField__Backdrop"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="Polaris-Icon multi-pieces-remove">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                        class="Polaris-Icon__Svg">
                                                        <path
                                                            d="M8 3.994C8 2.893 8.895 2 10 2s2 .893 2 1.994h4c.552 0 1 .446 1 .997 0 .55-.448.997-1 .997H4c-.552 0-1-.447-1-.997s.448-.997 1-.997h4zM5 14.508V8h2v6.508a.5.5 0 00.5.498H9V8h2v7.006h1.5a.5.5 0 00.5-.498V8h2v6.508A2.496 2.496 0 0112.5 17h-5C6.12 17 5 15.884 5 14.508z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <button type="button"
                                                    class="Polaris-Button Polaris-Button--primary multi-pieces-add">
                                                    <span class="Polaris-Button__Content">
                                                        <span class="Polaris-Button__Text textC">Add More</span>
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Layout">
                    <div class="Polaris-Layout__Section">
                        <div class="Polaris-Card">
                            <div class="Polaris-Card__Header">
                                <h2 class="Polaris-Heading">Shipment Details</h2>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div class="Polaris-FormLayout">
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label id="PolarisTextField1Label" for="Shipment-Weight"
                                                                class="Polaris-Label__Text">Shipment Weight (G)</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField Polaris-TextField--hasValue">
                                                                <input type="number" id="Shipment-Weight"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Handover-Method"
                                                                class="Polaris-Label__Text">Handover Method</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Select">
                                                        <select id="Handover-Method" class="Polaris-Select__Input">
                                                            <option value="2">Pickup</option>
                                                            <option value="1" selected>Drop off</option>
                                                        </select>
                                                        <div class="Polaris-Select__Content">
                                                            <span class="Polaris-Select__SelectedOption">Drop off</span>
                                                            <span class="Polaris-Select__Icon">
                                                                <span class="Polaris-Icon">
                                                                    <span class="Polaris-VisuallyHidden">
                                                                    </span>
                                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                                        <path
                                                                            d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                        </path>
                                                                    </svg>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div class="Polaris-Select__Backdrop">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Currency"
                                                                class="Polaris-Label__Text">Currency</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Select">
                                                        <input id="Currency" type="hidden">
                                                        <div class="Polaris-Select__Content">
                                                            <span class="Polaris-Select__SelectedOption">Please
                                                                Select</span>
                                                        </div>
                                                        <div class="Polaris-Select__Backdrop">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Pickup-Date" class="Polaris-Label__Text">Pickup
                                                                Date</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="date" id="Pickup-Date"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Package-Description"
                                                                class="Polaris-Label__Text">Package Description</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div
                                                                class="Polaris-TextField Polaris-TextField--hasValue Polaris-TextField--multiline">
                                                                <textarea id="Package-Description" class="Polaris-TextField__Input" type="text" rows="4"
                                                                    style="height: 106px;"></textarea>
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Remarks"
                                                                class="Polaris-Label__Text">Remarks</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div
                                                                class="Polaris-TextField Polaris-TextField--hasValue Polaris-TextField--multiline">
                                                                <textarea id="Remarks" class="Polaris-TextField__Input" type="text" rows="4" style="height: 106px;"></textarea>
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Layout">
                    <div class="Polaris-Layout__Section">
                        <div class="Polaris-Card" id="Shipper-Address-Section">
                            <div class="Polaris-Card__Header">
                                <h2 class="Polaris-Heading">Shipper Address</h2>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div class="Polaris-FormLayout">
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Company-Name" class="Polaris-Label__Text">Company
                                                                Name</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Company-Name"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="State"
                                                                class="Polaris-Label__Text">State</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="State"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Name" class="Polaris-Label__Text">Name</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Name"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="District"
                                                                class="Polaris-Label__Text">District</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="District"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Address-Line-1"
                                                                class="Polaris-Label__Text">Address Line 1</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Address-Line-1"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Country"
                                                                class="Polaris-Label__Text">Country</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Select">
                                                        <select id="Country" class="Polaris-Select__Input">
                                                            <option value="">Please select</option>

                                                        </select>

                                                        <div class="Polaris-Select__Content">
                                                            <span class="Polaris-Select__SelectedOption">United
                                                                States</span>
                                                            <span class="Polaris-Select__Icon">
                                                                <span class="Polaris-Icon">
                                                                    <span class="Polaris-VisuallyHidden">
                                                                    </span>
                                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                                        <path
                                                                            d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                        </path>
                                                                    </svg>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div class="Polaris-Select__Backdrop">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Address-Line-2"
                                                                class="Polaris-Label__Text">Address Line 2</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Address-Line-2"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Postcode"
                                                                class="Polaris-Label__Text">Postcode</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Postcode"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Address-Line-3"
                                                                class="Polaris-Label__Text">Address Line 3</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Address-Line-3"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Phone"
                                                                class="Polaris-Label__Text">Phone</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Phone"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="City" class="Polaris-Label__Text">City</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="City"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Email"
                                                                class="Polaris-Label__Text">Email</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Email"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Layout">
                    <div class="Polaris-Layout__Section">
                        <div class="Polaris-Card" id="Shipper-Address-Section">
                            <div class="Polaris-Card__Header">
                                <h2 class="Polaris-Heading">Return Address</h2>
                            </div>
                            <div class="Polaris-Card__Section">
                                <div class="Polaris-FormLayout">
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Mode" class="Polaris-Label__Text">Return
                                                                Mode</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Select">
                                                        <select id="Return-Mode" class="Polaris-Select__Input">
                                                            <option value="">Please select</option>
                                                            <option value="01">Return to Registered Address</option>
                                                            <option value="02">Return to Pickup Address</option>
                                                            <option value="03">Return to New Address</option>
                                                            <option value="05">Abandon</option>
                                                        </select>

                                                        <div class="Polaris-Select__Content">
                                                            <span class="Polaris-Select__SelectedOption">Please
                                                                select</span>
                                                            <span class="Polaris-Select__Icon">
                                                                <span class="Polaris-Icon">
                                                                    <span class="Polaris-VisuallyHidden">
                                                                    </span>
                                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                                        <path
                                                                            d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                        </path>
                                                                    </svg>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div class="Polaris-Select__Backdrop">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="Polaris-Card__Section" id="Return-Address-Section" style="display: none;">
                                <div class="Polaris-FormLayout">
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Company-Name"
                                                                class="Polaris-Label__Text">Company Name</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-Company-Name"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-State"
                                                                class="Polaris-Label__Text">State</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-State"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Name"
                                                                class="Polaris-Label__Text">Name</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-Name"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-District"
                                                                class="Polaris-Label__Text">District</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-District"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Address-Line-1"
                                                                class="Polaris-Label__Text">Address line 1</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-Address-Line-1"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Country"
                                                                class="Polaris-Label__Text">Country</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Select Polaris-TextField--disabled">
                                                        <select id="Return-Country" class="Polaris-Select__Input"
                                                            disabled>
                                                            <option value="">Please select</option>

                                                        </select>

                                                        <div class="Polaris-Select__Content">
                                                            <span class="Polaris-Select__SelectedOption">United
                                                                States</span>
                                                            <span class="Polaris-Select__Icon">
                                                                <span class="Polaris-Icon">
                                                                    <span class="Polaris-VisuallyHidden">
                                                                    </span>
                                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                                        <path
                                                                            d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z">
                                                                        </path>
                                                                    </svg>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div class="Polaris-Select__Backdrop">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Address-Line-2"
                                                                class="Polaris-Label__Text">Address line 2</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-Address-Line-2"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Postcode"
                                                                class="Polaris-Label__Text">Postcode</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-Postcode"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Address-Line-3"
                                                                class="Polaris-Label__Text">Address line 3</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-Address-Line-3"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Phone"
                                                                class="Polaris-Label__Text">Phone</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-Phone"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="Polaris-FormLayout--condensed">
                                        <div class="Polaris-FormLayout__Items">
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="City" class="Polaris-Label__Text">City</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-City"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="Polaris-FormLayout__Item">
                                                <div class="">
                                                    <div class="Polaris-Labelled__LabelWrapper">
                                                        <div class="Polaris-Label">
                                                            <label for="Return-Email"
                                                                class="Polaris-Label__Text">Email</label>
                                                        </div>
                                                    </div>
                                                    <div class="Polaris-Connected">
                                                        <div
                                                            class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                            <div class="Polaris-TextField">
                                                                <input type="text" id="Return-Email"
                                                                    class="Polaris-TextField__Input">
                                                                <div class="Polaris-TextField__Backdrop">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="Polaris-PageActions">
                            <div class="Polaris-Stack Polaris-Stack--spacingTight Polaris-Stack--distributionTrailing">
                                <div class="Polaris-Stack__Item">
                                    <button type="button" class="Polaris-Button btn-x Polaris-Button--primary"
                                        id="Create-Label">
                                        <span class="Polaris-Button__Content">
                                            <span class="Polaris-Button__Text">Create Label</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
$(document).ready(function() {
    var configuration = {};
    var order = {};
    var pickup_accounts = {};
    var weightValidationBypass = false;
    var extraWeight = 0;
    var currentTotalWeight = 0;
    var initialPackageDescription = ''; // Store initial package description
    var initialShipmentWeight = ''; // Store initial shipment weight
    var initialCashOnDelivery = '<?php echo $payment_method[0] == 'Cash on Delivery (COD)' ? $total_price : ''; ?>'; // Store initial COD value

    // The try-catch block is used to safely parse JSON strings from PHP.
    try {
        configuration = <?php echo json_encode($configuration); ?>;
        order = <?php echo json_encode($order); ?>;
        pickup_accounts = <?php echo json_encode($pickup_accounts); ?>;
        currentTotalWeight = order.currentTotalWeight;
    } catch (Error) {}

    $(document).off('change', '#Paper-Proof-Delivery-Action').on('change', '#Paper-Proof-Delivery-Action', function() {
        $('#Customized-Instruction-Section').css('display', 'none');
        if ($(this).val() === '4') {
            $('#Customized-Instruction-Section').css('display', 'block');
        }
    });

    $(document).off('change', '#Paper-Proof-Delivery-Option').on('change', '#Paper-Proof-Delivery-Option', function() {
        if ($(this).val() === '0') {
            $(this).parents('.Polaris-Connected').children('.Polaris-Connected__Item').eq(1).find(
                '.Polaris-Select').addClass('Polaris-Select--disabled').find('select').prop(
                'disabled', true).val('').trigger('change');
        } else if ($(this).val() === '1') {
            $(this).parents('.Polaris-Connected').children('.Polaris-Connected__Item').eq(1).find(
                '.Polaris-Select').removeClass('Polaris-Select--disabled').find('select').prop(
                'disabled', false);
        }
    });

    $(document).off('change', '#Handover-Method').on('change', '#Handover-Method', function() {
        var _this = $(this);
        $('#Pickup-Date').prop('disabled', true).parent('.Polaris-TextField').addClass(
            'Polaris-TextField--disabled');
        $('#Shipper-Address-Section').find('input').prop('disabled', true).parent(
            '.Polaris-TextField').addClass('Polaris-TextField--disabled');
        $('#Shipper-Address-Section').find('select').prop('disabled', true).parent(
            '.Polaris-Select').addClass('Polaris-TextField--disabled');
        $('#Return-Mode').find('option[value="02"]').prop('disabled', true);

        if (_this.val() === '2') {
            $('#Pickup-Date').prop('disabled', false).parent('.Polaris-TextField').removeClass(
                'Polaris-TextField--disabled');
            $('#Shipper-Address-Section').find('input').prop('disabled', false).parent(
                '.Polaris-TextField').removeClass('Polaris-TextField--disabled');
            $('#Shipper-Address-Section').find('select').prop('disabled', false).parent(
                '.Polaris-Select').removeClass('Polaris-TextField--disabled');
            $('#Return-Mode').find('option[value="02"]').prop('disabled', false);
        }
    });

    $(document).off('change', '#Multi-Pieces-Shipment').on('change', '#Multi-Pieces-Shipment', function() {
        $('#Delivery-Option-Section').css('display', 'none');
        $('#Multi-Pieces-Block-Form').css('display', 'none');
        $('#Multi-Pieces-Block-Section').css('display', 'none');
        $('#Shipment-Weight').prop('disabled', false).parent('.Polaris-TextField').removeClass('Polaris-TextField--disabled');
        if ($(this).prop('checked') === true) {
            $('#Delivery-Option-Section').css('display', 'block');
            $('#Multi-Pieces-Block-Form').css('display', 'block');
            $('#Multi-Pieces-Block-Section').css('display', 'block');
            $('#Shipment-Weight').prop('disabled', true).parent('.Polaris-TextField').addClass('Polaris-TextField--disabled');
            // Clear existing rows and add a single new empty row
            $('.multi-pieces-block-items').empty();
            var newRow = $('.multi-pieces-block-html').find('.multi-pieces-block').clone(true);
            newRow.find('input').val(''); // Clear all input fields in the new row
            $('.multi-pieces-block-items').append(newRow);
            setShipmentWeight();
            // Only update COD and SVP if their options are enabled and inputs have values
            if ($('#Cash-On-Delivery-Option').val() === '1') {
                var hasCODValue = false;
                $('.multi-pieces-block-items .Cash-On-Delivery').each(function() {
                    var iVal = $(this).val();
                    if (iVal.length > 0 && !isNaN(iVal) && iVal > 0) {
                        hasCODValue = true;
                        return false; // Break the loop
                    }
                });
                if (hasCODValue) {
                    setCashOnDelivery();
                }
            }
            if ($('#Shipment-Value-Protection-Option').val() === '1') {
                var hasSVPValue = false;
                $('.multi-pieces-block-items .Shipment-Insurance').each(function() {
                    var iVal = $(this).val();
                    if (iVal.length > 0 && !isNaN(iVal)) {
                        hasSVPValue = true;
                        return false; // Break the loop
                    }
                });
                if (hasSVPValue) {
                    setShipmentValueProtection();
                }
            }
        } else {
            // Clear all rows and restore initial values
            $('.multi-pieces-block-items').empty();
            $('#Package-Description').val(initialPackageDescription);
            $('#Shipment-Weight').val(initialShipmentWeight);
            // Restore initial COD value if COD is enabled
            if ($('#Cash-On-Delivery-Option').val() === '1') {
                $('#Cash-On-Delivery').val(initialCashOnDelivery);
            }
            if ($('#Shipment-Value-Protection-Option').val() === '1') {
                $('#Shipment-Value-Protection').val('0');
            }
        }
    });

    $(document).off('change', '#Return-Mode').on('change', '#Return-Mode', function() {
        $('#Return-Address-Section').hide();
        if ($(this).val() === '03') {
            $('#Return-Address-Section').show();
        }
    });

    $(document).off('change', '#Cash-On-Delivery-Option').on('change', '#Cash-On-Delivery-Option', function() {
        if ($(this).val() === '0') {
            $('#Cash-On-Delivery')
                .val('')
                .prop('disabled', true)
                .parent('.Polaris-TextField')
                .addClass('Polaris-TextField--disabled');
            $('.Cash-On-Delivery')
                .val('')
                .prop('disabled', true)
                .parent('.Polaris-TextField')
                .addClass('Polaris-TextField--disabled');
        } else if ($(this).val() === '1') {
            $('#Cash-On-Delivery')
                .prop('disabled', false)
                .parent('.Polaris-TextField')
                .removeClass('Polaris-TextField--disabled');
            $('.Cash-On-Delivery')
                .prop('disabled', false)
                .parent('.Polaris-TextField')
                .removeClass('Polaris-TextField--disabled');
            // Set initial COD value if MPS is not enabled
            if (!$('#Multi-Pieces-Shipment').prop('checked')) {
                $('#Cash-On-Delivery').val(initialCashOnDelivery);
            } else {
                setCashOnDelivery(); // Update COD total when enabling
            }
        }
    });

    $(document).off('change', '#Shipment-Value-Protection-Option').on('change', '#Shipment-Value-Protection-Option', function() {
        if ($(this).val() === '0') {
            $('#Shipment-Value-Protection')
                .val('')
                .prop('disabled', true)
                .parent('.Polaris-TextField')
                .addClass('Polaris-TextField--disabled');
            $('.Shipment-Insurance')
                .val('')
                .prop('disabled', true)
                .parent('.Polaris-TextField')
                .addClass('Polaris-TextField--disabled');
        } else if ($(this).val() === '1') {
            $('#Shipment-Value-Protection')
                .prop('disabled', false)
                .parent('.Polaris-TextField')
                .removeClass('Polaris-TextField--disabled');
            $('.Shipment-Insurance')
                .prop('disabled', false)
                .parent('.Polaris-TextField')
                .removeClass('Polaris-TextField--disabled');
            setShipmentValueProtection(); // Update SVP total when enabling
        }
    });

    $(document).off('change', '#Pickup-Account').on('change', '#Pickup-Account', function() {
        var _this = $(this);
        if (_this.val() !== '') {
            setPickupAccountDetails(pickup_accounts, _this.val(), configuration);
        }
    });

    $(document).off('click', '.multi-pieces-remove').on('click', '.multi-pieces-remove', function() {
        var rowCount = $('.multi-pieces-block-items .multi-pieces-block').length;
        if (rowCount > 1) {
            // More than one row: delete the row and update totals
            $(this).parents('.multi-pieces-block').remove();
            setShipmentWeight();
            // Set COD and SVP to 0 if their options are enabled and no valid values remain
            if ($('#Cash-On-Delivery-Option').val() === '1') {
                var hasCODValue = false;
                $('.multi-pieces-block-items .Cash-On-Delivery').each(function() {
                    var iVal = $(this).val();
                    if (iVal.length > 0 && !isNaN(iVal) && iVal > 0) {
                        hasCODValue = true;
                        return false; // Break the loop
                    }
                });
                if (hasCODValue) {
                    setCashOnDelivery();
                } else {
                    $('#Cash-On-Delivery').val(initialCashOnDelivery);
                }
            }
            if ($('#Shipment-Value-Protection-Option').val() === '1') {
                var hasSVPValue = false;
                $('.multi-pieces-block-items .Shipment-Insurance').each(function() {
                    var iVal = $(this).val();
                    if (iVal.length > 0 && !isNaN(iVal)) {
                        hasSVPValue = true;
                        return false; // Break the loop
                    }
                });
                if (hasSVPValue) {
                    setShipmentValueProtection();
                } else {
                    $('#Shipment-Value-Protection').val('0');
                }
            }
        } else {
            // Only one row: uncheck the Multi-Pieces-Shipment checkbox
            $('#Multi-Pieces-Shipment').prop('checked', false).trigger('change');
        }
    });

    $(document).off('click', '.multi-pieces-add').on('click', '.multi-pieces-add', function() {
        var multiPiecesBlockHtml = $('.multi-pieces-block-html').find('.multi-pieces-block').clone(true);
        multiPiecesBlockHtml.find('input').val(''); // Clear inputs in new row
        $('.multi-pieces-block-items').append(multiPiecesBlockHtml);
        setShipmentWeight(); // Only update shipment weight
    });

    $(document).off('click', '#Extra-Weight-Save').on('click', '#Extra-Weight-Save', function() {
        var extraWeight = $('#Extra-Shipment-Weight').val();
        if (empty(extraWeight)) {
            showToast('error', "Please add missing weight.");
            return false;
        }
        extraWeight = parseFloat(extraWeight);
        weightValidationBypass = true;
        $('#Create-Label').trigger('click');
        $('#Polaris-MoreWeight-Modal').modalClose();
    });

    $(document).off('click', '.Polaris-Modal-CloseButton').on('click', '.Polaris-Modal-CloseButton', function() {
        $(this).parents('.Polaris-Modal-Dialog__Container').modalClose();
    });

    $(document).off('click', '#Create-Label').on('click', '#Create-Label', function() {
        var productsWithZeroWeight = [];
        if (!empty(order.products)) {
            $.each(order.products, function(index, product) {
                if (product.variant.weight == 0)
                    productsWithZeroWeight.push(product);
            });
        }
        var _this = $(this);
        var dataObj = {};
        dataObj.pickupAccount = $('#Pickup-Account').val();
        dataObj.handoverMethod = $('#Handover-Method').val();
        dataObj.pickupDate = $('#Pickup-Date').val();
        dataObj.consolidatedLabel = $('#Consolidated-Label').val();
        dataObj.shipmentID = $('#Shipment-ID').data('sid');
        dataObj.packageDescription = $('#Package-Description').val();
        dataObj.productCode = $('#Product-Code').val();
        dataObj.cashOnDelivery = $('#Cash-On-Delivery-Option').val() == '1' ? $('#Cash-On-Delivery')
            .val() : null;
        dataObj.shipmentValueProtection = $('#Shipment-Value-Protection-Option').val() == '1' ? $(
            '#Shipment-Value-Protection').val() : null;
        dataObj.paperProofDeliveryOption = $('#Paper-Proof-Delivery-Option').val();
        dataObj.openBox = $('#Open-Box').val();
        dataObj.currency = $('#Currency').val();
        dataObj.remarks = $('#Remarks').val();
        dataObj.multiPiecesShipment = $('#Multi-Pieces-Shipment').prop('checked');
        dataObj.order = order;
        dataObj.returnMode = $('#Return-Mode').val();
        dataObj.shipmentWeight = parseFloat($('#Shipment-Weight').val()) + extraWeight;
        dataObj.returnAddress = {};
        dataObj.shop = "{{ request('shop') }}";

        if (dataObj.returnMode == '03') {
            dataObj.returnAddress = {
                companyName: $('#Return-Company-Name').val(),
                state: $('#Return-State').val(),
                name: $('#Return-Name').val(),
                district: $('#Return-District').val(),
                address1: $('#Return-Address-Line-1').val(),
                address2: $('#Return-Address-Line-2').val(),
                address3: $('#Return-Address-Line-3').val(),
                country: $('#Return-Country').val(),
                postcode: $('#Return-Postcode').val(),
                phone: $('#Return-Phone').val(),
                email: $('#Return-Email').val(),
                city: $('#Return-City').val(),
            };
        }

        if (dataObj.handoverMethod == '2') {
            if (empty(dataObj.pickupDate)) {
                showToast('error', "Please enter pickup date.");
                return false;
            }
        }

        if (empty(dataObj.pickupAccount)) {
            showToast('error', "Please select pickup account.");
            return false;
        }

        if (empty(dataObj.packageDescription)) {
            showToast('error', "Please enter package description.");
            return false;
        }

        if (empty(dataObj.returnMode)) {
            showToast('error', "Please select return mode.");
            return false;
        }

        dataObj.packageDescription = dataObj.packageDescription.substring(0, 200);
        if (!empty(dataObj.remarks)) {
            dataObj.remarks = dataObj.remarks.substring(0, 200);
        }

        if (dataObj.multiPiecesShipment === true) {
            dataObj.deliveryOption = $('input[name=Delivery-Option]:checked').val();
            if (dataObj.deliveryOption == undefined) {
                showToast('error', "Please select delivery option.");
                return false;
            }

            var multiPiecesBlock = $('.multi-pieces-block-items .multi-pieces-block');
            var shipmentPieces = [];
            var isError = false;

            $.each(multiPiecesBlock, function(index, item) {
                var pieceDescription = $(item).find('.Piece-Description').val();
                var weight = parseFloat($(item).find('.Shipment-Weight').val());
                var billingReference1 = $(item).find('.Billing-Ref1').val();
                var billingReference2 = $(item).find('.Billing-Ref2').val();
                var insuranceAmount = parseFloat($(item).find('.Shipment-Insurance').val());
                var codAmount = parseFloat($(item).find('.Cash-On-Delivery').val());

                if (empty(pieceDescription)) {
                    showToast('error', `Please enter piece description in row ${index + 1}`);
                    return false;
                }

                if (empty(weight)) {
                    showToast('error', `Please enter shipment weight in row ${index + 1}`);
                    return false;
                }

                var shipmentPiecesObj = {
                    pieceID: index + 1,
                    announcedWeight: {
                        weight,
                        unit: "G"
                    },
                    pieceDescription
                };

                if (!empty(codAmount))
                    shipmentPiecesObj.codAmount = codAmount;

                if (!empty(insuranceAmount))
                    shipmentPiecesObj.insuranceAmount = insuranceAmount;

                if (!empty(billingReference1))
                    shipmentPiecesObj.billingReference1 = billingReference1;

                if (!empty(billingReference2))
                    shipmentPiecesObj.billingReference2 = billingReference2;

                shipmentPieces.push(shipmentPiecesObj);
            });
        }

        if (empty(dataObj.shipmentWeight)) {
            showToast('error', "Please enter Shipment Weight.");
            return false;
        }

        if (!empty(shipmentPieces)) {
            dataObj.shipmentPieces = JSON.stringify(shipmentPieces);
        }

        _this.btnLoading(true);
        utils.getSessionToken(app).then(function(token) {
            $.ajax({
                type: 'POST',
                url: "{{ route('shippinglabel.store') }}",
                data: JSON.stringify(dataObj),
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Authorization': 'Bearer ' + token,
                    'shop': dataObj.shop
                },
                success: function(response) {
                    if (response.status === true) {
                        showToast('success', response.message);
                        setTimeout(() => {
                            window.parent.location.href =
                                "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/shipping-label/view/" + order.id;
                        }, 3000);
                    } else {
                        if (typeof response.message != 'undefined') {
                            showToast('error', response.message);
                        } else if (response[0]?.length > 0) {
                            showToast('error', response[0]);
                        } else {
                            showToast('error', 'Something went wrong!');
                        }
                        _this.btnLoading(false);
                    }
                },
                error: function(err) {
                    showToast('error', 'Request failed.');
                    _this.btnLoading(false);
                }
            });
        }).catch(function(error) {
            showToast('error', 'Authentication failed.');
        });
    });

    $(document).off('keyup', '.Shipment-Insurance').on('keyup', '.Shipment-Insurance', function(e) {
        if (e.key.match(/[0-9]/) || e.key === 'Backspace' || e.key === 'Delete') {
            setShipmentValueProtection();
        }
    });

    $(document).off('keyup', '.Cash-On-Delivery').on('keyup', '.Cash-On-Delivery', function(e) {
        if (e.key.match(/[0-9]/) || e.key === 'Backspace' || e.key === 'Delete') {
            setCashOnDelivery();
        }
    });

    $(document).off('keyup', '.Shipment-Weight').on('keyup', '.Shipment-Weight', function() {
        setShipmentWeight();
    });

    function setShipmentWeight() {
        var totalWeight = 0;
        $('.multi-pieces-block-items .Shipment-Weight').each(function() {
            var weight = parseFloat($(this).val()) || 0;
            totalWeight += weight;
        });
        $('#Shipment-Weight').val(Math.floor(totalWeight)); // Use integer weight
    }

    function setCashOnDelivery() {
        if ($('#Cash-On-Delivery-Option').val() === '1') {
            var totalCOD = 0;
            $('.multi-pieces-block-items .Cash-On-Delivery').each(function() {
                var iVal = $(this).val();
                totalCOD += (iVal.length > 0 && !isNaN(iVal) && iVal > 0) ? parseFloat(iVal) : 0;
            });
            $('#Cash-On-Delivery').val(totalCOD);
        }
    }

    function setShipmentValueProtection() {
        if ($('#Shipment-Value-Protection-Option').val() === '1') {
            var totalShipmentInsurance = 0;
            $('.multi-pieces-block-items .Shipment-Insurance').each(function() {
                var iVal = $(this).val();
                totalShipmentInsurance += (iVal.length > 0 && !isNaN(iVal)) ? parseFloat(iVal) : 0;
            });
            $('#Shipment-Value-Protection').val(totalShipmentInsurance);
        }
    }

    function setOrderDetails(configuration, order, pickup_accounts) {
        if (empty(order))
            return false;

        $.each(pickup_accounts, function(index, account) {
            if (account.is_default === '1')
                $('#Pickup-Account').val(account.id);
        });

        var shipmentID = !empty(configuration.prefix) ? configuration.prefix : '';
        shipmentID = shipmentID + (!empty(order.name) ? order.name.substring(1) : '');
        initialShipmentWeight = !empty(order.variantWeight) ? order.variantWeight : ''; // Store initial weight
        $('#Shipment-ID').val(shipmentID).data('sid', shipmentID);
        $('#Shipment-Weight').val(initialShipmentWeight);

        $('#Currency').val(configuration.currency).parent('.Polaris-Select').find(
            '.Polaris-Select__SelectedOption').text(configuration.currency);
        $('#Customer-Name').text(order.customer.defaultAddress.name);
        $('#Customer-Address1').text(order.customer.defaultAddress.address1);
        $('#Customer-Address2').text(order.customer.defaultAddress.address2);
        $('#Customer-City').text(order.customer.defaultAddress.city);
        $('#Customer-Province').text(order.customer.defaultAddress.province);
        $('#Customer-Zip').text(order.customer.defaultAddress.zip);
        $('#Customer-CountryCode').text(order.customer.defaultAddress.countryCodev2);
        $('#Customer-Phone').text(order.customer.phone);
        $('#Customer-Email').text(order.customer.email);
        $('#Shipping-Address-phone').text(order.customer.defaultAddress.phone);

        var lineItems = order.products;
        var productNames = '';
        $.each(lineItems, function(index, lineItem) {
            productNames += lineItem.name + ',';
        });
        initialPackageDescription = productNames.slice(0, -1); // Store initial package description
        $('#Package-Description').val(initialPackageDescription);
        $('#Remarks').val(initialPackageDescription);
    };

    function setPickupAccountDetails(pickup_accounts, id, configuration) {
        var pickupAccount = {};
        $.each(pickup_accounts, function(index, account) {
            if (account.id == id) {
                pickupAccount = account;
                pickupAccount.country = configuration.country;
            }
        });

        $('#Company-Name').val(pickupAccount.company);
        $('#State').val(pickupAccount.state);
        $('#Name').val(pickupAccount.name);
        $('#District').val(pickupAccount.district);
        $('#Address-Line-1').val(pickupAccount.address_line_1);
        $('#Address-Line-2').val(pickupAccount.address_line_2);
        $('#Address-Line-3').val(pickupAccount.address_line_3);
        $('#Country').val(pickupAccount.country);
        $('#Postcode').val(pickupAccount.postcode);
        $('#Phone').val(pickupAccount.phone);
        $('#Email').val(pickupAccount.email);
        $('#City').val(pickupAccount.city);
    };

    $('#Paper-Proof-Delivery-Option').trigger('change');
    $('#Paper-Proof-Delivery-Action').trigger('change');
    $('#Cash-On-Delivery-Option').trigger('change');
    $('#Shipment-Value-Protection-Option').trigger('change');
    $('#Handover-Method').trigger('change');
    $('#Return-Mode').val("01").trigger('change');
    $('#Pickup-Account').trigger('change');
    // Trigger MPS change last to ensure COD value is preserved
    $('#Multi-Pieces-Shipment').trigger('change');

    // set order details pending
    setOrderDetails(configuration, order, pickup_accounts);
});
    </script>
@endsection