@extends('layouts.master')

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

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('shippinglabel.storeBulkLabels') }}">
        @csrf

        <!-- Hidden Inputs for Order IDs -->
        @foreach($orderIds as $id)
            <input type="hidden" name="ids[]" value="{{ $id }}">
        @endforeach

        <!-- Shipping Product Selection -->
        <div class="Polaris-Layout">
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Card">
                    <div class="Polaris-Card__Header">
                        <h2 class="Polaris-Heading">Shipping product</h2>
                    </div>
                    <div class="Polaris-Card__Section">
                        <label class="Polaris-Label__Text">Search products</label>
                        <select id="shipping_product" name="shipping_product_id" class="Polaris-Select__Input" required>
                            <option value="">Search products...</option>
                            @foreach($shippingProducts as $product)
                                <option value="{{ $product['id'] }}">{{ $product['name'] }} - {{ $product['product_code'] }}</option>
                            @endforeach
                        </select>
                        <div style="margin-top:6px"><small class="text-muted">Why is my shipping product not here?</small></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Options -->
        <div class="Polaris-Layout">
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Card">
                    <div class="Polaris-Card__Header">
                        <h2 class="Polaris-Heading">Available options</h2>
                    </div>
                    <div class="Polaris-Card__Section">
                        <label class="Polaris-Choice" for="shipment_value_protection">
                            <span class="Polaris-Choice__Control"><span class="Polaris-Checkbox"><input id="shipment_value_protection" type="checkbox" name="shipment_value_protection" class="Polaris-Checkbox__Input"></span></span>
                            <span class="Polaris-Choice__Label">Additional insurance</span>
                        </label>
                        <label class="Polaris-Choice" for="open_box">
                            <span class="Polaris-Choice__Control"><span class="Polaris-Checkbox"><input id="open_box" type="checkbox" name="open_box" class="Polaris-Checkbox__Input"></span></span>
                            <span class="Polaris-Choice__Label">Open Box</span>
                        </label>
                        <label class="Polaris-Choice" for="paper_proof_of_delivery">
                            <span class="Polaris-Choice__Control"><span class="Polaris-Checkbox"><input id="paper_proof_of_delivery" type="checkbox" name="paper_proof_of_delivery" class="Polaris-Checkbox__Input"></span></span>
                            <span class="Polaris-Choice__Label">Paper Proof of Delivery</span>
                        </label>
                        <label class="Polaris-Choice" for="cash_on_delivery">
                            <span class="Polaris-Choice__Control"><span class="Polaris-Checkbox"><input id="cash_on_delivery" type="checkbox" name="cash_on_delivery" class="Polaris-Checkbox__Input"></span></span>
                            <span class="Polaris-Choice__Label">Cash on Delivery</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fulfillment Details -->
        <div class="Polaris-Layout">
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Card">
                    <div class="Polaris-Card__Header"><h2 class="Polaris-Heading">Find fulfillment by order name, location…</h2></div>
                    <div class="Polaris-Card__Section">
                        <input type="text" id="fulfillment_search" class="Polaris-TextField__Input" placeholder="Search fulfillments..." style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <div class="Polaris-DataTable" style="margin-top:12px">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select_all"></th>
                                        <th>Name</th>
                                        <th>Creation date</th>
                                        <th>Fulfillment location</th>
                                        <th>Destination</th>
                                        <th>Weight</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="fulfillments_table">
                                    @foreach($fulfillments as $fulfillment)
                                        <tr>
                                            <td><input type="checkbox" name="fulfillments[]" value="{{ $fulfillment->id }}"></td>
                                            <td>{{ $fulfillment->name }}</td>
                                            <td>{{ $fulfillment->creation_date }}</td>
                                            <td>{{ $fulfillment->location }}</td>
                                            <td>{{ $fulfillment->destination }}</td>
                                            <td>{{ $fulfillment->weight }}</td>
                                            <td></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Labels Button -->
        <div class="Polaris-Layout">
            <div class="Polaris-Layout__Section">
                <button type="submit" class="Polaris-Button Polaris-Button--primary"><span class="Polaris-Button__Content"><span class="Polaris-Button__Text">Create labels</span></span></button>
            </div>
        </div>
    </form>

    <!-- JS for Search and Select All -->
    <script>
        document.getElementById('fulfillment_search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#fulfillments_table tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        document.getElementById('select_all').addEventListener('change', function() {
            document.querySelectorAll('input[name="fulfillments[]"]').forEach(cb => cb.checked = this.checked);
        });
    </script>
</div>
@endsection