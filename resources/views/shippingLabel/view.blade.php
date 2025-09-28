@extends('layouts.master')

@section('moduleName')
    View shipping label
@endsection

@section('style')
    <style>
        .link-edit-label {
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    <div class="Polaris-Page">
        <div class="Polaris-Page-Header">
            <div class="Polaris-Page-Header__MainContent">
                <div class="Polaris-Page-Header__TitleActionMenuWrapper">
                    <div>
                        <div class="Polaris-Header-Title__TitleAndSubtitleWrapper">
                            <div class="Polaris-Header-Title">
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">View Shipping Label</h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="Polaris-Page-Header__RightAlign">
                    <div class="Polaris-Page-Header__Actions">
                        <div class="Polaris-ButtonGroup">
                            <!-- <div class="Polaris-ButtonGroup__Item"><a download href="
                            // $label_data['label']
                            " class="Polaris-Button" aria-disabled="true" type="button" tabindex="-1"><span class="Polaris-Button__Content"><span class="Polaris-Button__Text">Download</span></span></a></div> -->
                            <div class="Polaris-ButtonGroup__Item">
                                <a {{-- href="{{ route('shippinglabel.delete') . '?shipmentId=' . $shipment_id . '&orderId=' . $order_id . '&shop=' . request('shop') . '&token=' . request('token') }}" --}} class="Polaris-Button Polaris-Button--destructive"
                                    id="Delete-Shipment" data-shipid="{{ $shipment_id }}" data-id="{{ $order_id }}" aria-disabled="true" type="button"
                                    tabindex="-1"><span class="Polaris-Button__Content"><span
                                            class="Polaris-Button__Text">Cancel</span></span>
                                </a>
                            </div>
                            <div class="Polaris-ButtonGroup__Item"><a {{-- href="{{ route('shippinglabel.edit', request('order_id')) . '?shop=' . request('shop') . '&token=' . request('token') }}" --}}
                                    class="Polaris-Button Polaris-Button--primary link-edit-label"
                                    data-id="{{ request('order_id') }}" aria-disabled="true" type="button"
                                    tabindex="-1"><span class="Polaris-Button__Content"><span
                                            class="Polaris-Button__Text">Edit</span></span></a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="Polaris-Page__Content">
            <div class="Polaris-Layout">

                <?php
                if(!empty($generated_labels)){
                    foreach ($generated_labels as $key => $generated_label) {
                ?>
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Layout">
                        <?php
                        if(isset($generated_label['label']) && !empty($generated_label['label'])){
                        ?>
                        <div class="Polaris-Layout__Section Polaris-Layout__Section--oneThird">
                            <div class="Polaris-Card">
                                <div class="Polaris-Card__Header">
                                    <div class="Polaris-Stack Polaris-Stack--alignmentBaseline">
                                        <div class="Polaris-Stack__Item Polaris-Stack__Item--fill">
                                            <h2 class="Polaris-Text--root Polaris-Text--headingMd Polaris-Text--semibold">
                                                Shipping Label</h2>
                                        </div>
                                        <div class="Polaris-Stack__Item">
                                            <div class="Polaris-ButtonGroup">
                                                <div class="Polaris-ButtonGroup__Item Polaris-ButtonGroup__Item--plain">
                                                    <a download href="{{ $generated_label['label'] }}"
                                                        class="Polaris-Button Polaris-Button--plain" type="button">
                                                        <span class="Polaris-Button__Content"><span
                                                                class="Polaris-Button__Text">Download</span></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="Polaris-Card__Section">
                                    <div>
                                        <?php
                                        if ($generated_label['extension'] == 'png') {
                                        ?>
                                        <img src="{{ $generated_label['label'] }}" class="label-image">
                                        <?php
                                        } else {
                                        ?>
                                        <a target="_blank" href="{{ $generated_label['label'] }}"
                                            class="Polaris-Button Polaris-Button--primary" aria-disabled="true"
                                            type="button" tabindex="-1"><span class="Polaris-Button__Content"><span
                                                    class="Polaris-Button__Text">View
                                                    PDF</span></span></a>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                        <?php
                         if(isset($generated_label['ppod']) && !empty($generated_label['ppod'])){
                        ?>
                        <div class="Polaris-Layout__Section Polaris-Layout__Section--oneThird">
                            <div class="Polaris-Card">
                                <div class="Polaris-Card__Header">
                                    <div class="Polaris-Stack Polaris-Stack--alignmentBaseline">
                                        <div class="Polaris-Stack__Item Polaris-Stack__Item--fill">
                                            <h2 class="Polaris-Text--root Polaris-Text--headingMd Polaris-Text--semibold">
                                                PPOD Label</h2>
                                        </div>
                                        <div class="Polaris-Stack__Item">
                                            <div class="Polaris-ButtonGroup">
                                                <div class="Polaris-ButtonGroup__Item Polaris-ButtonGroup__Item--plain">
                                                    <a download
                                                        href="
                                                            {{ $generated_label['ppod'] }}
                                                            "
                                                        class="Polaris-Button Polaris-Button--plain" type="button">
                                                        <span class="Polaris-Button__Content"><span
                                                                class="Polaris-Button__Text">Download</span></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="Polaris-Card__Section">
                                    <div>
                                        <?php
                                        if ($generated_label['extension'] == 'png') {
                                        ?>
                                        <img src="{{ $generated_label['ppod'] }}" class='label-image'>
                                        <?php
                                         }
                                         else {
                                        ?>
                                        <a target="_blank" href="{{ $generated_label['ppod'] }}"
                                            class="Polaris-Button Polaris-Button--primary" aria-disabled="true"
                                            type="button" tabindex="-1"><span class="Polaris-Button__Content"><span
                                                    class="Polaris-Button__Text">View
                                                    PDF</span></span></a>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                        <?php
                        if(isset($generated_label['delivery_confirmation_no']) && !empty($generated_label['delivery_confirmation_no'])){
                        ?>
                        <div class="Polaris-Layout__Section Polaris-Layout__Section--secondary">
                            <div class="Polaris-Card">
                                <div class="Polaris-Card__Header">
                                    <h2 class="Polaris-Heading">Delivery Confirmation No</h2>
                                </div>
                                <div class="Polaris-Card__Section">
                                    <p>
                                        <span id="Customer-Name">{{ $generated_label['delivery_confirmation_no'] }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.link-edit-label', function() {
                var id = $(this).attr('data-id');
                window.parent.location.href =
                    "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/shipping-label/edit/" +
                    id;
            });

            $(document).on('click', '#Delete-Shipment', function() {
                var id = $(this).attr('data-id');
                var shipid = $(this).attr('data-shipid');
                window.parent.location.href =
                    "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/shipping-label/delete?orderId=" +
                    id+"&shipmentId="+shipid;
            });

            @if (request('deleted') == 'yes')
                showToast('info', 'Failed to delete label!');
            @endif
        });
    </script>
@endsection
