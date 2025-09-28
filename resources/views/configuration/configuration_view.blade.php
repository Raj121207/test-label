@extends('layouts.master')

@section('moduleName')
    Configuration
@endsection

@section('content')
    <div class="Polaris-Page">
        <div class="Polaris-Page-Header">
            <div class="Polaris-Page-Header__MainContent">
                <div class="Polaris-Page-Header__TitleActionMenuWrapper">
                    <div>
                        <div class="Polaris-Header-Title__TitleAndSubtitleWrapper">
                            <div class="Polaris-Header-Title">
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Configuration</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="Polaris-Layout">
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Card">
                    <div class="Polaris-Card__Section">
                        <div class="Polaris-FormLayout">
                            <div role="group" class="Polaris-FormLayout--condensed">
                                <div class="Polaris-FormLayout__Items">
                                    <div class="Polaris-FormLayout__Item">
                                        <div class="">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label for="Location-Country" class="Polaris-Label__Text">Location
                                                        Country</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Select">
                                                <select id="Location-Country" class="Polaris-Select__Input">
                                                    <option value="">Please select</option>
                                                    <?php
                                                    $countryList = config('constant.COUNTRY_LIST');
                                                    ?>
                                                    @if (!empty(config('constant.COUNTRY_LIST')))
                                                        @foreach ($countryList as $country)
                                                            @if (isset($country['display']) && $country['display'] === true)
                                                                <option value="{{ $country['code'] }}"
                                                                    currency="{{ $country['currency'] }}">
                                                                    {{ $country['name'] }}</option>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                    ?>
                                                </select>
                                                <div class="Polaris-Select__Content">
                                                    <span class="Polaris-Select__SelectedOption">Please select</span>
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
                                                    <label for="Account-Type" class="Polaris-Label__Text">Account
                                                        Type</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-TextField">
                                                        <input type="text" id="Account-Type"
                                                            class="Polaris-TextField__Input" value="DHL eCommerce Asia"
                                                            disabled>
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
                                                    <label for="Soldto-Account" class="Polaris-Label__Text">Soldto
                                                        Account</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-TextField">
                                                        <input type="text" id="Soldto-Account"
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
                                                    <label for="Pickup-Account" class="Polaris-Label__Text">Pickup
                                                        Account</label>
                                                </div>
                                                <div class="Polaris-Labelled__Action"><a
                                                         href="{{ route('pickup.showpickup') . '?shop=' . request('shop') . '&token=' . request('token') }}"
                                                        class="Polaris-Button Polaris-Button--plain add-new-pickup" type="button"><span
                                                            class="Polaris-Button__Content"><span
                                                                class="Polaris-Button__Text">Add Account</span></span></a>
                                                </div>
                                            </div>
                                            <div class="Polaris-Select">

                                                <select id="Pickup-Account" class="Polaris-Select__Input">
                                                    <option value="">Please select</option>

                                                    @if (count($pickupAccounts) > 0)
                                                        @if (isset($pickupAccounts) && !empty($pickupAccounts))
                                                            @foreach ($pickupAccounts as $pickup_account)
                                                                <option value="{{ $pickup_account['id'] }}"
                                                                    {{ $pickup_account['is_default'] == 1 ? 'selected' : '' }}>
                                                                    {{ $pickup_account['number'] }} -
                                                                    {{ $pickup_account['company'] }}</option>
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                </select>
                                                <div class="Polaris-Select__Content">
                                                    <span class="Polaris-Select__SelectedOption">Please select</span>
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
                                                    <label for="Label-Shipping" class="Polaris-Label__Text">Enable DHL
                                                        Shipping Label</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Select">
                                                <select id="Label-Shipping" class="Polaris-Select__Input" >
                                                    <option value="1" selected>Yes</option>
                                                    <option value="0" selected>No</option>
                                                </select>
                                                <div class="Polaris-Select__Content">
                                                    <span class="Polaris-Select__SelectedOption">4 x 1</span>
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
                                                    <label for="product_code" class="Polaris-Label__Text">Product
                                                        Code</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Select">

                                                <select id="product_code" class="Polaris-Select__Input">
                                                    <option value="">Please select</option>
                                                    <option value="PDO" selected="selected">Parcel Domestic</option>
                                                    <option value="PDR">DHL Parcel Return</option>
                                                    <option value="PDE">Parcel Domestic Expedited</option>
                                                    <option value="DDO">Document Domestic</option>
                                                    <option value="SDP">DHL Parcel Metro</option>
                                                </select>
                                                <div class="Polaris-Select__Content">
                                                    <span class="Polaris-Select__SelectedOption">Please select</span>
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
                                                    <label for="Prefix" class="Polaris-Label__Text">Prefix</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-TextField">
                                                        <input type="text" id="Prefix"
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
                    <div class="Polaris-Card__Section">
                        <div class="Polaris-FormLayout">
                            <div role="group" class="Polaris-FormLayout--condensed">
                                <div class="Polaris-FormLayout__Items">
                                    <div class="Polaris-FormLayout__Item">
                                        <div class="">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label for="Client-Secret" class="Polaris-Label__Text">Client
                                                        Secret/Password</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-TextField">
                                                        <input type="text" id="Client-Secret"
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
                                                    <label for="Client-ID" class="Polaris-Label__Text">Client ID</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-TextField Polaris-TextField--hasValue">
                                                        <input type="text" id="Client-ID"
                                                            class="Polaris-TextField__Input">
                                                        <div class="Polaris-TextField__Backdrop"></div>
                                                    </div>
                                                </div>
                                                <div class="Polaris-Connected__Item">
                                                    <button id="Connect-Account"
                                                        class="Polaris-Button Polaris-Button--destructive"
                                                        type="button"><span class="Polaris-Button__Content"><span
                                                                class="Polaris-Button__Text">Connect</span></span></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="Polaris-Card__Section">
                        <div class="Polaris-Card__SectionHeader">
                            <h3 class="Polaris-Subheading">Optional Settings</h3>
                        </div>
                        <div class="Polaris-FormLayout">
                            <div role="group" class="Polaris-FormLayout--condensed">
                                <div class="Polaris-FormLayout__Items">
                                    <div class="Polaris-FormLayout__Item">
                                        <div class="">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label for="Label-Template" class="Polaris-Label__Text">Label
                                                        Template</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Select">
                                                <select id="Label-Template" class="Polaris-Select__Input">
                                                    <option value="1">1 x 1</option>
                                                    <option selected value="2">4 x 1</option>
                                                </select>
                                                <div class="Polaris-Select__Content">
                                                    <span class="Polaris-Select__SelectedOption">4 x 1</span>
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
                                                    <label for="Label-Format" class="Polaris-Label__Text">Label
                                                        Format</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Select">
                                                <select id="Label-Format" class="Polaris-Select__Input">
                                                    <option value="PDF">PDF</option>
                                                    <option selected value="PNG">PNG</option>
                                                </select>
                                                <div class="Polaris-Select__Content">
                                                    <span class="Polaris-Select__SelectedOption">PNG</span>
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
                    <div class="Polaris-Card__Footer">
                        <div class="Polaris-ButtonGroup">
                            <div class="Polaris-ButtonGroup__Item">
                                <button class="Polaris-Button Polaris-Button--primary" id="Save-Configuration">
                                    <span class="Polaris-Button__Content">
                                        <span class="Polaris-Button__Text">Save</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@section('script')
    <script>
        var configuration = {};

        try {
            configuration = JSON.parse('<?= json_encode($configuration) ?>');
        } catch (Error) {}

        if (!empty(configuration)) {
            if (Object.entries(configuration).length)
                setFormData(configuration);
        }

        /* UPDATE TIME EXISTING FORM DATA SET */
        function setFormData(formData) {

            $("#Location-Country").val(formData.country).trigger('change');
            // $("#Account-Type").val(formData.account_type);
            $('#Soldto-Account').val(formData.soldto_account);
            // $('#Pickup-Account').val(formData.pickup_account).trigger('change');
            $('#Label-Shipping').val(formData.enable_shipping).trigger('change');
            $('#product_code').val(formData.product_code).trigger('change');
            $('#Prefix').val(formData.prefix);
            $('#Client-ID').val(formData.client_id);
            $('#Client-Secret').val(formData.client_secret);
            $('#Label-Template').val(formData.label_template).trigger('change');
            $('#Label-Format').val(formData.label_format).trigger('change');
        }

        $(document).on('click', '#Connect-Account', function() {
            var dataObj = {};
            dataObj.clientID = $('#Client-ID').val();
            dataObj.clientSecret = $('#Client-Secret').val();

            if (dataObj.clientSecret == null || dataObj.clientSecret == "") {
                showToast('error', "Enter Client Secret");
                return false;
            }

            if (dataObj.clientID == null || dataObj.clientID == "") {
                showToast('error', "Enter Client Id");
                return false;
            }
            utils.getSessionToken(app).then(function(token) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('configuration.auth') }}",
                    data: dataObj,
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.message === 'Authentication has been successful.') {
                            showToast('success', response.message);
                        } else {
                            showToast('error', response.message);
                            return false;
                        }
                    },
                    error: function(xhr) {
                        showToast('error', 'Request failed.');
                    }
                });
            }).catch(function(error) {
                showToast('error', 'Authentication failed.');
            });

        });

        $(document).on('click', '#Save-Configuration', function() {

            var dataObj = {
                country: $('#Location-Country').val(),
                currency: $('#Location-Country option:selected').attr('currency'),
                accountType: $('#Account-Type').val(),
                soldtoAccount: $('#Soldto-Account').val(),
                pickupAccount: $('#Pickup-Account').val(),
                labelShipping: $('#Label-Shipping').val(),
                productCode: $('#product_code').val(),
                prefix: $('#Prefix').val(),
                clientID: $('#Client-ID').val(),
                clientSecret: $('#Client-Secret').val(),
                labelTemplate: $('#Label-Template').val(),
                labelFormat: $('#Label-Format').val()
            };

            if (dataObj.country == null || dataObj.country == "") {
                showToast('error', "Country is required");
                return false;
            }
            if (dataObj.currency == null || dataObj.currency == "") {
                showToast('error', "Currency is required");
                return false;
            }
            if (dataObj.accountType == null || dataObj.accountType == "") {
                showToast('error', "Account Type is required");
                return false;
            }
            if (dataObj.soldtoAccount == null || dataObj.soldtoAccount == "") {
                showToast('error', "Sold to Account is required");
                return false;
            }
            if (dataObj.pickupAccount == null || dataObj.pickupAccount == "") {
                showToast('error', "Pickup Account is required");
                return false;
            }
            if (dataObj.productCode == null || dataObj.productCode == "") {
                showToast('error', "Product Code is required");
                return false;
            }
            if (dataObj.prefix == null || dataObj.prefix == "") {
                showToast('error', "Prefix is required");
                return false;
            }
            if (dataObj.clientID == null || dataObj.clientID == "") {
                showToast('error', "Enter Client Id");
                return false;
            }
            if (dataObj.clientSecret == null || dataObj.clientSecret == "") {
                showToast('error', "Enter Client Secret");
                return false;
            }
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            utils.getSessionToken(app).then(function(token){
                    $.ajax({
                        type: 'POST',
                        url: "{{ route('configuration.add') }}",
                        data: dataObj,
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.message === 'Configuration details have been saved successfully.') {
                                showToast('success', response.message);
                            } else {
                                showToast('error', response.message);
                                return false;
                            }
                        },
                        error: function(xhr) {
                            showToast('error', 'Request failed.');
                        }
                    });
                }).catch(function(error) {
                    showToast('error', 'Authentication failed.');
                });
            });

        $(document).on('click', '.add-new-pickup', function() {
            window.parent.location.href =
                "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/add-pickup";
        });
    </script>
@endsection

@endsection
