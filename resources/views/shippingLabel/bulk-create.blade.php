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
                            @if (!empty($unfulfilledOrders))
                                <div class="Polaris-Banner__Content">
                                    <p>Unfulfilled orders:</p>
                                    <ul style="list-style-type: disc; margin-left: 20px; margin-top: 8px;">
                                        @foreach ($unfulfilledOrders as $orderName)
                                            <li>{{ $orderName }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($noFulfillments))
            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Banner Polaris-Banner--statusWarning Polaris-Banner--withinPage">
                        <div class="Polaris-Banner__ContentWrapper">
                            <div class="Polaris-Banner__Heading"><p class="Polaris-Heading">Unfulfilled orders detected</p></div>
                            <div class="Polaris-Banner__Content">
                                <p>The following orders are not fulfilled and must be fulfilled before creating labels:</p>
                                <ul style="list-style-type: disc; margin-left: 20px; margin-top: 8px;">
                                    @foreach ($unfulfilledOrders as $orderName)
                                        <li>{{ $orderName }}</li>
                                    @endforeach
                                </ul>
                                <p>
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
            $errors = array_filter($bulk_response ?? [], fn($r) => ($r['status'] ?? '') === 'error');
            $hasErrors = !empty($errors);
            $successCount = count(array_filter($bulk_response ?? [], fn($r) => ($r['status'] ?? '') === 'success'));
            $hasSuccess = $successCount > 0;
        @endphp

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
                @php
                    $uniqueOrderIds = [];
                @endphp
                @foreach ($orders as $o)
                    @if (!in_array($o['id'], $uniqueOrderIds))
                        @php $uniqueOrderIds[] = $o['id']; @endphp
                        <input type="hidden" name="ids[]" value="{{ $o['id'] }}">
                    @endif
                @endforeach
            @endif

            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section Polaris-Layout__Section--oneThird">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Header"><h2 class="Polaris-Heading">Shipping product</h2></div>
                        <div class="Polaris-Card__Section">
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
                        </div>
                    </div>
                </div>

                @if (empty($noFulfillments))
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

            <div class="Polaris-Card__Section" style="text-align:right;">
                @if (!empty($noFulfillments))
                    <span class="Polaris-TextStyle--variationSubdued">Cannot create labels due to unfulfilled orders</span>
                    <button type="submit" class="Polaris-Button Polaris-Button--primary" id="createLabelsBtn" disabled style="opacity:0.5;">
                        Create labels
                    </button>
                @elseif ($hasErrors && $successCount === 0)
                    <span class="Polaris-TextStyle--variationSubdued">No available labels to create</span>
                    <button type="submit" class="Polaris-Button Polaris-Button--primary" id="createLabelsBtn" disabled style="opacity:0.5;">
                        Create labels
                    </button>
                @elseif ($hasSuccess)
                    <span class="Polaris-TextStyle--variationSubdued">{{ $successCount }} label(s) generated successfully</span>
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
                            @if ($hasSuccess)
                                <div style="margin-bottom:12px; text-align:right;">
                                    <button type="button" 
                                            class="Polaris-Button Polaris-Button--primary"
                                            id="downloadAllLabelsBtn">
                                        <span class="Polaris-Button__Content">
                                            <span class="Polaris-Button__Text">Download All Labels</span>
                                        </span>
                                    </button>
                                    <button type="button" 
                                            class="Polaris-Button"
                                            id="printAllLabelsBtn">
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
                                            <th>Fulfillment Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ordersTableBody">
                                        @php
                                            $uniqueOrderIds = [];
                                        @endphp
                                        @forelse ($orders as $index => $o)
                                            @if (!in_array($o['id'], $uniqueOrderIds))
                                                @php $uniqueOrderIds[] = $o['id']; @endphp
                                                <tr data-id="{{ $o['id'] }}">
                                                    <td>{{ $o['name'] }}</td>
                                                    <td>{{ date('d-m-Y', strtotime($o['createdAt'])) }}</td>
                                                    <td>{{ $o['shippingAddress']['address1'] ?? '-' }}</td>
                                                    <td>{{ $o['destination'] ?? '-' }}</td>
                                                    <td>{{ number_format((float)$o['weight'], 0) }} g</td>
                                                    <td>{{ ucfirst($o['fulfillment_status'] ?? 'Unknown') }}</td>
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
                                            @endif
                                        @empty
                                            <tr class="no-items-row">
                                                <td colspan="7" style="text-align:center; color:#999; padding:16px;">
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
        (function(){
            var form = document.getElementById('bulkForm');
            var submit = document.getElementById('createLabelsBtn');
            if(form && submit){
                form.addEventListener('submit', function(e){
                    var rowsPresent = document.querySelectorAll('#ordersTableBody tr:not(.no-items-row)').length > 0;
                    if(!rowsPresent){
                        e.preventDefault();
                        alert('Please select at least one order.');
                        return false;
                    }
                    var existing = form.querySelectorAll('input[name="ids[]"]');
                    existing.forEach(function(n){ n.parentNode.removeChild(n); });
                    var rows = document.querySelectorAll('#ordersTableBody tr[data-id]');
                    var uniqueIds = new Set();
                    rows.forEach(function(r){
                        if(r.style.display === 'none') return;
                        var id = r.getAttribute('data-id');
                        if(id && !uniqueIds.has(id)){
                            uniqueIds.add(id);
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = id;
                            form.appendChild(input);
                        }
                    });
                    submit.disabled = true;
                });
            }

            // Remove existing event listeners for buttons
            var downloadBtn = document.getElementById('downloadAllLabelsBtn');
            var printBtn = document.getElementById('printAllLabelsBtn');
            if(downloadBtn){
                downloadBtn.replaceWith(downloadBtn.cloneNode(true));
            }
            if(printBtn){
                printBtn.replaceWith(printBtn.cloneNode(true));
            }

            // Rebind event listeners
            downloadBtn = document.getElementById('downloadAllLabelsBtn');
            printBtn = document.getElementById('printAllLabelsBtn');

            if(downloadBtn){
                downloadBtn.addEventListener('click', downloadAllLabels);
            }
            if(printBtn){
                printBtn.addEventListener('click', printAllLabels);
            }
        })();

        function removeRow(id){
            var row = document.querySelector('tr[data-id="'+id+'"]');
            if(row){ row.remove(); }
            var hidden = document.querySelector('input[type="hidden"][name="ids[]"][value="'+id+'"]');
            if(hidden){ hidden.remove(); }
            var count = document.querySelectorAll('input[name="ids[]"]').length;
            var countEl = document.getElementById('labelCount');
            if(countEl) countEl.innerText = count;
            var tbody = document.getElementById('ordersTableBody');
            var rowsLeft = tbody.querySelectorAll('tr[data-id]').length;
            if(rowsLeft === 0){
                tbody.innerHTML = `
                    <tr class="no-items-row">
                        <td colspan="7" style="text-align:center; color:#999; padding:20px;">
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
                        alert("Cannot create labels: Some orders are not fulfilled. Please fulfill all orders first.");
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
                var rows = document.querySelectorAll('#ordersTableBody tr[data-id]');
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

        function downloadAllLabels() {
            // Collect all label IDs from the table rows that have successful labels
            const labelIds = [];
            document.querySelectorAll('#ordersTableBody tr[data-id]').forEach(row => {
                const buttons = row.querySelectorAll('button[onclick*="downloadSingleLabel"]');
                if (buttons.length > 0) {
                    const onclick = buttons[0].getAttribute('onclick');
                    const match = onclick.match(/downloadSingleLabel\('[^']+', '([^']+)'\)/);
                    if (match && match[1]) {
                        labelIds.push(match[1]);
                    }
                }
            });

            if (labelIds.length === 0) {
                alert('No labels available to download.');
                return;
            }

            fetch('{{ route("shippinglabel.download-labels", ["shop" => request("shop")]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ labelIds: labelIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.downloadURL) {
                    downloadFile(data.downloadURL);
                } else {
                    alert('Error: ' + (data.error || 'Failed to download labels'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error downloading labels');
            });
        }

        function printAllLabels() {
            // Collect all label IDs from the table rows that have successful labels
            const labelIds = [];
            document.querySelectorAll('#ordersTableBody tr[data-id]').forEach(row => {
                const buttons = row.querySelectorAll('button[onclick*="printSingleLabel"]');
                if (buttons.length > 0) {
                    const onclick = buttons[0].getAttribute('onclick');
                    const match = onclick.match(/printSingleLabel\('[^']+', '([^']+)'\)/);
                    if (match && match[1]) {
                        labelIds.push(match[1]);
                    }
                }
            });

            if (labelIds.length === 0) {
                alert('No labels available to print.');
                return;
            }

            fetch('{{ route("shippinglabel.print-labels", ["shop" => request("shop")]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ labelIds: labelIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.printURL) {
                    printLabel(data.printURL);
                } else {
                    alert('Error: ' + (data.error || 'Failed to prepare labels for printing'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error preparing labels for printing');
            });
        }

        function downloadSingleLabel(orderId, labelId) {
            const downloadUrl = '{{ route("shippinglabel.download-direct", ["shop" => request("shop"), "label_id" => ":labelId"]) }}'.replace(':labelId', labelId);
            downloadFile(downloadUrl);
        }

        function printSingleLabel(orderId, labelId) {
            const printUrl = '{{ route("shippinglabel.print-direct", ["shop" => request("shop"), "label_id" => ":labelId"]) }}'.replace(':labelId', labelId);
            printLabel(printUrl);
        }
    </script>
@endsection