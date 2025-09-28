@extends('layouts.master')
@section('moduleName')
    Shipping label list
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

        .text-center {
            text-align: center;
        }

        .word-break {
            word-break: break-all
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
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Shipping Labels</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="Polaris-Page__Content bundle-list-table">
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
                            <table id="labels-table">
                                <thead>
                                    <tr>
                                        <th>Order Id</th>
                                        <th>Shipment Id</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($labels as $label)
                                        <tr>
                                            <td>{{ $label->order_name }}</td>
                                            <td>{{ $label->shipment_id }}</td>
                                            <td>
                                                <div class="Polaris-ButtonGroup">
                                                    <div class="Polaris-ButtonGroup__Item"><a
                                                            {{-- href="{{ route('shippinglabel.view', ['order_id' => $label->order_id]) . '?shop=' . request('shop') . '&token=' . request('token') }}" --}}
                                                            class="Polaris-Button Polaris-Button--primary Polaris-Button--sizeSlim link-view-label" data-id="{{ $label->order_id }}"><span
                                                                class="Polaris-Button__Content"><span
                                                                    class="Polaris-Button__Text">View</span></span></a>
                                                    </div>
                                                    <div class="Polaris-ButtonGroup__Item"><a target="_blank"
                                                            href="{{ config('services.dhl.tracking_url') . $label->shipment_id }}"
                                                            class="Polaris-Button Polaris-Button--primary Polaris-Button--sizeSlim"><span
                                                                class="Polaris-Button__Content"><span
                                                                    class="Polaris-Button__Text">Tracking</span></span></a>
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
            $(document).on('click', '.link-view-label', function() {
                var _id = $(this).attr('data-id');
                window.parent.location.href =
                            "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/shipping-label/view/" + _id;
            });

            $('#labels-table').DataTable({
                "autoWidth": false,
                "responsive": false,
                "lengthChange": false,
                "searching": false,
                "processing": false,
                "language": {
                    "infoFiltered": ""
                },
                order: [
                    [0, "desc"]
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [1, 2]
                }],
                "columns": [{
                        "data": "order_name",
                        'width': '25%'
                    },
                    {
                        "data": "shipment_id",
                        'width': '50%'
                    },
                    {
                        "data": "action",
                        'width': '25%',
                    }
                ]
            });

            @if (request('exists') == 'yes')
                showToast('info', 'Shipping label is already generated');
            @endif

            @if (request('deleted') == 'yes')
                showToast('info', 'Shipping label is deleted!');
            @endif
        });

    </script>
@endsection
