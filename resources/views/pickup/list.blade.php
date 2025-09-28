@extends('layouts.master')

@section('moduleName')
    Pickup Accounts
@endsection

@section('style')
    <style>
        .Polaris-Spinner__Container {
            display: none;
        }

        .Polaris-Spinner__Container {
            display: none;
        }

        .Polaris-Spinner__Container.Spinner_Show {
            align-items: center;
            justify-content: center;
            display: flex;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: #ffffffbf;
            z-index: 2000;
            background-size: 70px;
        }

        .Polaris-Spinner__Container.Spinner_Show .Polaris-Spinner__Content {
            max-width: 50%;
            text-align: center;
        }

        .Polaris-Spinner__Container.Spinner_Show .Polaris-Spinner__Title {
            font-weight: 600;
            font-family: serif, fantasy;
            font-size: 20px;
            margin-top: 2rem;
        }

        .polaris-spinner {
            margin-top: 20px;
            text-align: center;
        }

        .polaris-switch {
            width: 36px;
            height: 18px;
            margin: .1rem 0;
        }

        .polaris-switch input {
            display: none;
        }

        .polaris-switch input:checked+label {
            background: var(--p-action-primary);
            border-color: var(--p-action-primary);
        }

        .polaris-switch input:checked+label:before {
            left: 20px;
            background: #FFF;
        }

        .polaris-switch label {
            width: 36px;
            height: 18px;
            display: block;
            background: #ffffff;
            border: 1px solid #ccc;
            border-radius: 20px;
            position: relative;
            transition: .3s ease-in-out;
            cursor: pointer;
        }

        .polaris-switch label:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 2px;
            width: 12px;
            height: 12px;
            background: var(--p-icon);
            border-radius: 12px;
            margin: auto 0;
            transition: .3s ease-in-out;
        }

        @media only screen and (max-width: 1199px) {
            .polaris-switch {
                width: 32px;
                height: 16px;
                margin: .2rem 0;
            }

            .polaris-switch input:checked+label:before {
                left: 18px;
            }

            .polaris-switch label {
                width: 32px;
                height: 16px;
            }

            .polaris-switch label:before {
                width: 10px;
                height: 10px;
            }
        }

        a.link-edit-pick>i {
            padding: 6px 5px 5px 8px;
            border-radius: 5px;
            cursor: pointer;
        }

        a.link-delete-pick>i {
            padding: 6px 8px 5px 8px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-addnew-pick {
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
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Pickup accounts</h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="Polaris-Page-Header__RightAlign">
                    <div class="Polaris-Page-Header__Actions">

                        <div class="Polaris-Page-Header__PrimaryActionWrapper"><a
                                class="Polaris-Button Polaris-Button--primary btn-addnew-pick" aria-disabled="true"
                                type="button" tabindex="-1"><span class="Polaris-Button__Content"><span
                                        class="Polaris-Button__Text">Add</span></span></a></div>

                    </div>
                </div>
            </div>
        </div>
        <div class="Polaris-Page__Content">
            <div class="Polaris-Layout">

                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Section" style="position: relative;">
                            <div class="Polaris-Spinner__Container" id="listing-loader">
                                <div class="Polaris-Spinner__Content">
                                    <span class="Polaris-Spinner Polaris-Spinner--sizeLarge">
                                        <svg viewBox="0 0 44 44" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M15.542 1.487A21.507 21.507 0 00.5 22c0 11.874 9.626 21.5 21.5 21.5 9.847 0 18.364-6.675 20.809-16.072a1.5 1.5 0 00-2.904-.756C37.803 34.755 30.473 40.5 22 40.5 11.783 40.5 3.5 32.217 3.5 22c0-8.137 5.3-15.247 12.942-17.65a1.5 1.5 0 10-.9-2.863z">
                                            </path>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <table id="acconut-table" class="w-100">
                                <thead>
                                    <tr>
                                        <th>Pickup account</th>
                                        <th>Company name</th>
                                        <th>Name</th>
                                        <th>Postcode</th>
                                        {{-- <th>Phone</th>
                                        <th>Email</th> --}}
                                        <th>Default</th>
                                        <th>Enable</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pickupAccount as $pa)
                                        <tr>
                                            <td>{{ $pa->number }}</td>
                                            <td>{{ $pa->company }}</td>
                                            <td>{{ $pa->name }}</td>
                                            <td>{{ $pa->postcode }}</td>
                                            {{-- <td>{{ $pa->phone }}</td>
                                            <td>{{ $pa->email }}</td> --}}
                                            <td>
                                                <div class="polaris-switch default-change"><input
                                                        data-token="{{ $pa->id }}"
                                                        {{ $pa->is_default === '1' ? 'checked' : '' }} type="checkbox"
                                                        id="{{ $pa->number }}{{ $pa->id }}"><label
                                                        for="{{ $pa->number }}{{ $pa->id }}"></label></div>
                                            </td>
                                            <td>
                                                <div class="polaris-switch status-change"><input
                                                        data-token="{{ $pa->id }}"
                                                        {{ $pa->status === '1' ? 'checked' : '' }} type="checkbox"
                                                        id="{{ $pa->number }}{{ $pa->id }}3"><label
                                                        for="{{ $pa->number }}{{ $pa->id }}3"></label></div>
                                            </td>
                                            <td>
                                                <div class="Polaris-ButtonGroup">
                                                    <div class="Polaris-ButtonGroup__Item"><a data-id="{{ $pa->id }}"
                                                            {{-- href="{{ route('pickup.editpickup', $pa->id) . '?shop=' . request('shop') . '&token=' . request('token') }}" --}} class="link-edit-pick"><i
                                                                class="fa fa-edit Polaris-Button--primary"></i></a>
                                                    </div>
                                                    <div class="Polaris-ButtonGroup__Item"><a
                                                            data-token="{{ $pa->id }}" class="link-delete-pick"><i
                                                                class="fa fa-trash-o Polaris-Button--destructive pickup-delete"></i></a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
            
            $(document).on('click', '.link-edit-pick', function() {
                var id = $(this).attr('data-id');
                window.parent.location.href =
                    "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/pickup-accounts/" +
                    id;
            });

            $(document).on('click', '.btn-addnew-pick', function() {
                window.parent.location.href =
                    "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/add-pickup";
            });

            var acconutList = $('#acconut-table').DataTable({
                autoWidth: false,
                responsive: false,
                lengthChange: false,
                searching: false,
                processing: true,
                serverSide: false,
                pageLength: 5,
                language: {
                    infoFiltered: ''
                },
                order: [
                    [0, "desc"]
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [1, 2, 3]
                }],
            });

            //Delete Pickup Account
            $(document).on('click', '.link-delete-pick', function() {
                var _this = $(this);
                if (confirm("Are you sure?")) {
                    var dataObj = {};
                    dataObj.id = _this.data('token');
                    dataObj._token = $('meta[name="csrf-token"]').attr('content');

                    utils.getSessionToken(app).then(function(token) {
                  $.ajax({
                    type: 'POST',
                    url: "{{ route('pickup.delete') }}",
                    data: dataObj,
                    headers: {
                      'Authorization': 'Bearer ' + token,
                      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                    if (response.status === true) {
                      showToast('success', response.message);
                      setTimeout(() => {
                        location.reload();
                      }, 3000);
                    } else {
                        showToast('error', response.message);
                      return false;
                    }
                  }
                });
            }).catch(function(error) {
                showToast('error', 'Authentication failed.');
              });
                }
            });

            // Handle change event for default switch
            $(document).on('change', '.polaris-switch.default-change input', function() {
                var _this = $(this);
                var dataObj = {
                    id: _this.data('token'),
                    isDefault: _this.prop('checked')
                };

                var rowData = {
                    status: _this.closest('tr').find('.polaris-switch.status-change input').prop(
                        'checked')
                };

                if (rowData.status === false && dataObj.isDefault) {
                    _this.prop('checked', false);
                    showToast('error', "You cannot set default to a disabled account.");
                    return false;
                }

                // Show loading indicator
                _this.closest('.polaris-switch.default-change').addClass('loading');

              utils.getSessionToken(app).then(function(token) {
                  $.ajax({
                      type: 'POST',
                      url: "{{ route('pickup.default') }}",
                      data: dataObj,
                      headers: {
                          'Authorization': 'Bearer ' + token,
                          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                      },
                      success: function(response) {
                          if (response.status === true) {
                              $(".polaris-switch.default-change input").not(_this).prop('checked', false);
                          } else {
                              _this.prop('checked', false);
                              showToast('error', response.message);
                              return false;
                          }
                          acconutList.draw(false);
                      },
                      complete: function() {
                          _this.closest('.polaris-switch.default-change').removeClass('loading');
                      },
                      error: function(xhr) {
                          showToast('error', 'Request failed.');
                      }
                  });
              }).catch(function(error) {
                  showToast('error', 'Authentication failed.');
              });

            });

            // Handle change event for status switch
            $(document).on('change', '.polaris-switch.status-change input', function() {
                var _this = $(this);
                var dataObj = {
                    id: _this.data('token'),
                    status: _this.prop('checked'),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                var rowData = {
                    is_default: _this.closest('tr').find('.polaris-switch.default-change input').prop(
                        'checked')
                };

                if (rowData.is_default === true && dataObj.status === false) {
                    _this.prop('checked', true);
                    showToast('error', "You cannot disable the default account.");
                    return false;
                }

                // Show loading indicator
                _this.closest('.polaris-switch.status-change').addClass('loading');
                utils.getSessionToken(app).then(function(token) {
                    $.ajax({
                        type: 'POST',
                        url: "{{ route('pickup.status') }}", // changed to your new route
                        data: dataObj,
                        headers: {
                            'Authorization': 'Bearer ' + token, // session token
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Laravel CSRF
                        },
                        success: function() {
                            acconutList.draw(false);
                        },
                        complete: function() {
                            _this.closest('.polaris-switch.status-change').removeClass('loading');
                        },
                        error: function(xhr) {
                            showToast('error', 'Request failed.');
                        }
                    });
                }).catch(function(error) {
                    showToast('error', 'Authentication failed.');
                });

            });
        });
    </script>
@endsection
