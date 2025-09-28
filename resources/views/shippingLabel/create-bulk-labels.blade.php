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

        @if (isset($labelsGenerated) && $labelsGenerated)
            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Banner Polaris-Banner--statusSuccess Polaris-Banner--withinPage">
                        <div class="Polaris-Banner__ContentWrapper">
                            <div class="Polaris-Banner__Heading"><p class="Polaris-Heading">Success</p></div>
                            <div class="Polaris-Banner__Content"><p>Labels generated successfully</p></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form id="bulkForm" action="{{ route('shippinglabel.bulkCreate', ['shop' => request('shop')]) }}" method="GET">
            <input type="hidden" name="shop" value="{{ request('shop') }}">
            @if (!empty($orders))
                @foreach ($orders as $o)
                    <input type="hidden" name="ids[]" value="{{ $o['id'] }}">
                @endforeach
            @endif

            <div class="Polaris-Layout">
                <!-- Left: Shipping product panel -->
                <div class="Polaris-Layout__Section Polaris-Layout__Section--oneThird">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Header"><h2 class="Polaris-Heading">Shipping product</h2></div>
                        <div class="Polaris-Card__Section">
                            <div class="Polaris-TextField" style="margin-bottom:12px;">
                                <input class="Polaris-TextField__Input" type="text" placeholder="Search products" disabled>
                            </div>
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
                                            <div style="flex:1;overflow:hidden;">
                                                <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $product['name'] }}
                                                </div>
                                                <div style="font-size:12px;color:#637381;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $activePickup->company }} - #{{ substr($activePickup->number, 0, 8) }}…
                                                </div>
                                            </div>
                                            <span style="font-size:14px;color:#637381;margin-left:4px;">ℹ️</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div style="color:#999;font-size:14px;">No active pickup account or product code found</div>
                                @endif
                            </div>
                            <div style="margin-top:10px;">
                                <a href="javascript:void(0)">Why is my shipping product not here?</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Available options + handover + button -->
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
                            <div id="handoverDisplay" style="font-weight:bold;">Drop-off</div>
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
                                        <option value="Pickup">Pickup</option>
                                        <option value="Drop-off" selected>Drop-off</option>
                                    </select>
                                    <div class="Polaris-Select__Content">
                                        <span class="Polaris-Select__SelectedOption" id="handoverSelected">Drop-off</span>
                                        <span class="Polaris-Select__Icon">
                                            <span class="Polaris-Icon">
                                                <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                    <path d="M7.676 9h4.648c.563 0 .879-.603.53-1.014l-2.323-2.746a.708.708 0 0 0-1.062 0l-2.324 2.746c-.347.411-.032 1.014.531 1.014Zm4.648 2h-4.648c-.563 0-.878.603-.53 1.014l2.323 2.746c.27.32.792.32 1.062 0l2.323-2.746c.349-.411.033-1.014-.53-1.014Z"></path>
                                                </svg>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="Polaris-Card__Section" style="text-align:right;">
                @if (isset($labelsGenerated) && $labelsGenerated)
                    <span class="Polaris-TextStyle--variationSubdued">No available labels to create</span>
                @else
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
                            @if (isset($labelsGenerated) && $labelsGenerated)
                                <div style="margin-bottom:12px; text-align:right;">
                                    <a href="{{ $downloadURL }}" download class="Polaris-Button Polaris-Button--primary">
                                        <span class="Polaris-Button__Content">
                                            <span class="Polaris-Button__Text">Download Labels</span>
                                        </span>
                                    </a>
                                    <button type="button" class="Polaris-Button" onclick="window.print()">
                                        <span class="Polaris-Button__Content">
                                            <span class="Polaris-Button__Text">Print Labels</span>
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
                                                @if (isset($labelsGenerated) && $labelsGenerated)
                                                    <a href="{{ asset('storage/app/' . $labels_dir . '/' . $prefix . substr($o['name'], 1) . '.' . strtolower($configuration['label_format'] ?? 'pdf')) }}" download class="Polaris-Button">
                                                        <span class="Polaris-Button__Content">
                                                            <span class="Polaris-Button__Text">Download</span>
                                                        </span>
                                                    </a>
                                                    <button type="button" class="Polaris-Button" onclick="printLabel('{{ asset('storage/app/' . $labels_dir . '/' . $prefix . substr($o['name'], 1) . '.' . strtolower($configuration['label_format'] ?? 'pdf')) }}')">
                                                        <span class="Polaris-Button__Content">
                                                            <span class="Polaris-Button__Text">Print</span>
                                                        </span>
                                                    </button>
                                                @else
                                                    <button type="button" class="Polaris-Button Polaris-Button--destructive" title="Remove" onclick="removeRow('{{ $o['id'] }}')">
                                                        <span class="Polaris-Button__Content"><span class="Polaris-Button__Text">🗑</span></span>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6">No orders selected.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if (isset($labelsGenerated) && $labelsGenerated)
                                <div style="margin-top:12px; text-align:right;">
                                    <a href="{{ $downloadURL }}" download class="Polaris-Button Polaris-Button--primary">
                                        <span class="Polaris-Button__Content">
                                            <span class="Polaris-Button__Text">Download All Labels</span>
                                        </span>
                                    </a>
                                    <button type="button" class="Polaris-Button" onclick="window.print()">
                                        <span class="Polaris-Button__Content">
                                            <span class="Polaris-Button__Text">Print Labels</span>
                                        </span>
                                    </button>
                                </div>
                            @endif
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
                form.addEventListener('submit', function(){
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
            var row = document.querySelector('tr[data-id="'+id+'"]');
            if(row){ row.parentNode.removeChild(row); }
            var hidden = document.querySelector('input[type="hidden"][name="ids[]"][value="'+id+'"]');
            if(hidden){ hidden.parentNode.removeChild(hidden); }
            var count = document.querySelectorAll('input[name="ids[]"]').length;
            document.getElementById('labelCount').innerText = count;
        }

        function printLabel(url) {
            const printWindow = window.open(url, '_blank');
            printWindow.onload = function() {
                printWindow.print();
            };
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
    </script>
@endsection