@extends('layouts.master')

@section('moduleName')
    @if (isset($pickupAccount) && !empty($pickupAccount))
        Edit Pickup
    @else
        Add Pickup
    @endif
@endsection

@section('content')
    <div class="Polaris-Page">
        <div class="Polaris-Page-Header">
            <div class="Polaris-Page-Header__MainContent">
                <div class="Polaris-Page-Header__TitleActionMenuWrapper">
                    <div>
                        <div class="Polaris-Header-Title__TitleAndSubtitleWrapper">
                            <div class="Polaris-Header-Title">
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Pickup Account Details</h1>
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
                                                    <label for="Pickup-Account-Number" class="Polaris-Label__Text">Pickup
                                                        Account Number <span class="red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-TextField">
                                                        <input type="text" id="Pickup-Account-Number"
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
                                                    <label for="Company-Name" class="Polaris-Label__Text">Company
                                                        Name</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                </div>
                            </div>
                            <div role="group" class="Polaris-FormLayout--condensed">
                                <div class="Polaris-FormLayout__Items">
                                    <div class="Polaris-FormLayout__Item">
                                        <div class="">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label for="Your-Name" class="Polaris-Label__Text">Pickup Account Name
                                                        <span class="red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-TextField">
                                                        <input type="text" id="Your-Name"
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
                                                    <label for="Address-Line-1" class="Polaris-Label__Text">Address Line 1
                                                        <span class="red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                </div>
                            </div>
                            <div role="group" class="Polaris-FormLayout--condensed">
                                <div class="Polaris-FormLayout__Items">
                                    <div class="Polaris-FormLayout__Item">
                                        <div class="">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label for="Address-Line-2" class="Polaris-Label__Text">Address Line
                                                        2</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                                    <label for="Address-Line-3" class="Polaris-Label__Text">Address Line
                                                        3</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                </div>
                            </div>
                            <div role="group" class="Polaris-FormLayout--condensed">
                                <div class="Polaris-FormLayout__Items">
                                    <div class="Polaris-FormLayout__Item">
                                        <div class="">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label for="City" class="Polaris-Label__Text">City <span
                                                            class="red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                                    <label for="State" class="Polaris-Label__Text">State</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                                    <label for="District" class="Polaris-Label__Text">District</label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                    <div class="Polaris-FormLayout__Item">
                                        <div class="">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label for="Post-Code" class="Polaris-Label__Text">Postcode <span
                                                            class="red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
                                                    <div class="Polaris-TextField">
                                                        <input type="text" id="Post-Code"
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
                                                    <label for="Phone" class="Polaris-Label__Text">Phone <span
                                                            class="red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                    <div class="Polaris-FormLayout__Item">
                                        <div class="">
                                            <div class="Polaris-Labelled__LabelWrapper">
                                                <div class="Polaris-Label">
                                                    <label for="Email" class="Polaris-Label__Text">Email <span
                                                            class="red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="Polaris-Connected">
                                                <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
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
                                <div class="Polaris-FormLayout__Items">
                                    <div class="Polaris-FormLayout__Item">
                                        <label class="Polaris-Choice" for="Is-Default">
                                            <span class="Polaris-Choice__Control">
                                                <span class="Polaris-Checkbox">
                                                    <input id="Is-Default" type="checkbox"
                                                        class="Polaris-Checkbox__Input" value="0">
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
                                            <span class="Polaris-Choice__Label">Set as Default</span>
                                        </label>
                                    </div>
                                    <div class="Polaris-FormLayout__Item">
                                        <label class="Polaris-Choice" for="Is-Enable">
                                            <span class="Polaris-Choice__Control">
                                                <span class="Polaris-Checkbox">
                                                    <input id="Is-Enable" type="checkbox" class="Polaris-Checkbox__Input"
                                                        value="0">
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
                                            <span class="Polaris-Choice__Label">Set as Enable</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="Polaris-Card__Footer">
                        <div class="Polaris-ButtonGroup">
                            <div class="Polaris-ButtonGroup__Item">
                                <button class="Polaris-Button Polaris-Button--primary" id="Save-Pickup">
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
        var pickupAccount = {};

        try {
            @if (isset($pickupAccount) && !empty($pickupAccount))
                pickupAccount = JSON.parse('<?= json_encode($pickupAccount) ?>');
            @endif
        } catch (Error) {}

        var pickupID = '';
        var actionURL = "{{ route('pickup.add') }}";
        if (!empty(pickupAccount)) {
            if (Object.entries(pickupAccount).length) {
                setPickupAccount(pickupAccount);
                actionURL = "{{ route('pickup.edit') }}";

                pickupID = pickupAccount.id;
            }
        } else {
            $('#Is-Enable').prop('checked', true);
            $('#Is-Enable').val('1');
        }

        /* UPDATE TIME EXISTING FORM DATA SET */
        function setPickupAccount(data) {
            $('#Pickup-Account-Number').val(data.number);
            $('#Company-Name').val(data.company);
            $('#Your-Name').val(data.name);
            $('#Address-Line-1').val(data.address_line_1);
            $('#Address-Line-2').val(data.address_line_2);
            $('#Address-Line-3').val(data.address_line_3);
            $('#City').val(data.city);
            $('#State').val(data.state);
            $('#District').val(data.district);
            $('#Post-Code').val(data.postcode);
            $('#Phone').val(data.phone);
            $('#Email').val(data.email);
            $('#Is-Default').val(data.is_default);
            $('#Is-Enable').val(data.status);
            $('#Is-Default').prop('checked', data.is_default == 1);
            $('#Is-Enable').prop('checked', data.status == 1);
        };

        $(document).on('click', '#Save-Pickup', function() {

            // Set value If the Set as Default checked
            if ($('#Is-Default').is(":checked") == true) {
                $('#Is-Default').val('1');
            } else {
                $('#Is-Default').val('0');
            }

            // Set value If the Set as Enable checked
            if ($('#Is-Enable').is(":checked") == true) {
                $('#Is-Enable').val('1');
            } else {
                $('#Is-Enable').val('0');
            }

            var dataObj = {
                accountNumber: $('#Pickup-Account-Number').val(),
                companyName: $('#Company-Name').val(),
                yourName: $('#Your-Name').val(),
                addressLine1: $('#Address-Line-1').val(),
                addressLine2: $('#Address-Line-2').val(),
                addressLine3: $('#Address-Line-3').val(),
                city: $('#City').val(),
                state: $('#State').val(),
                district: $('#District').val(),
                postcode: $('#Post-Code').val(),
                phone: $('#Phone').val(),
                email: $('#Email').val(),
                isDefault: $('#Is-Default').val(),
                status: $('#Is-Enable').val(),
                id: pickupID,
            };

            if (dataObj.isDefault === '1' && dataObj.status === '0') {
                showToast('error', "Default account must be enable.");
                return false;
            }

            if (dataObj.accountNumber == null || dataObj.accountNumber == "") {
                showToast('error', "Account number is requird.");
                return false;
            }
            if (dataObj.yourName == null || dataObj.yourName == "") {
                showToast('error', "Your name is requird.");
                return false;
            }
            if (dataObj.addressLine1 == null || dataObj.addressLine1 == "") {
                showToast('error', "Address line 1 is requird.");
                return false;
            }
            if (dataObj.city == null || dataObj.city == "") {
                showToast('error', "City is requird.");
                return false;
            }
            if (dataObj.postcode == null || dataObj.postcode == "") {
                showToast('error', "Postcode is requird.");
                return false;
            }
            if (dataObj.phone == null || dataObj.phone == "") {
                showToast('error', "Phone number is requird.");
                return false;
            }
            if (dataObj.email == null || dataObj.email == "") {
                showToast('error', "Email is requird.");
                return false;
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            utils.getSessionToken(app)
                .then(function(token) {
                    $.ajax({
                        type: 'POST',
                        url: actionURL,
                        data: JSON.stringify(dataObj),
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Authorization': 'Bearer ' + token
                        },
                        success: function(response) {
                            if (response.status === true) {
                                showToast('success', response.message);
                            } else {
                                showToast('error', response.message);
                                return false;
                            }
                            setTimeout(() => {
                                window.parent.location.href =
                                    "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/configuration";
                            }, 3000);
                        },
                        error: function(xhr, status, error) {
                            showToast('error', 'Request failed. Please try again.');
                        }
                    });
                })
                .catch(function(error) {
                    showToast('error', 'Session expired or token fetch failed.');
                });

        });
    </script>
@endsection
@endsection
