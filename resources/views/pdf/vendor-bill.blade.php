@php
    $lead = $vendorBill->invoice?->lead;
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
    $descLines = trim((string) ($vendorBill->service_details ?? ''));
    $billDate = $vendorBill->created_at;
    $dueDate = $vendorBill->invoice?->due_date;
    $terms = $vendorBill->invoice?->terms ?? 'Custom';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill {{ $vendorBill->vendor_bill_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            margin: 0;
            padding: 24px;
            line-height: 1.45;
        }
        .doc-header {
            font-size: 11px;
            color: #444;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #ddd;
        }
        .doc-header .buyer-name { font-weight: bold; color: #111; }
        table.banner { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.banner td { vertical-align: top; padding: 0 8px 0 0; }
        table.banner td.right-meta { text-align: right; padding-right: 0; }
        .label-muted { color: #555; font-size: 9px; text-transform: uppercase; letter-spacing: 0.03em; }
        .bill-from-title { font-weight: bold; margin-bottom: 6px; font-size: 11px; }
        .supplier-name { font-weight: bold; font-size: 12px; margin-bottom: 4px; }
        .meta-row { margin-bottom: 3px; }
        .meta-row strong { color: #333; }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 9px;
        }
        table.lines th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            color: #374151;
        }
        table.lines td {
            border: 1px solid #d1d5db;
            padding: 8px 6px;
            vertical-align: top;
        }
        table.lines th.num, table.lines td.num { text-align: right; white-space: nowrap; }
        table.lines th.ix, table.lines td.ix { width: 28px; text-align: center; }
        .item-title { font-weight: bold; display: block; margin-bottom: 4px; }
        .customer-details { color: #333; }
        .customer-details .ref { margin-top: 4px; font-size: 8px; color: #555; }
        table.totals {
            width: 280px;
            margin-left: auto;
            margin-top: 16px;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.totals td { padding: 6px 10px; border: 1px solid #e5e7eb; }
        table.totals td:first-child { color: #4b5563; }
        table.totals td:last-child { text-align: right; font-weight: 600; }
        table.totals tr.balance td {
            background: #f3f4f6;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <span class="buyer-name">{{ $company['name'] }}</span>
        <span style="color:#888;"> — Vendor bill</span>
    </div>

    <table class="banner">
        <tr>
            <td width="52%">
                <div class="bill-from-title">Bill From</div>
                <div class="supplier-name">{{ $vendorBill->supplier?->name ?? $vendorBill->vendor_name }}</div>
                @if($vendorBill->supplier?->address)
                    <div>{!! nl2br(e($vendorBill->supplier->address)) !!}</div>
                @endif
            </td>
            <td class="right-meta" width="48%">
                <div class="meta-row"><strong>Order Number:</strong> {{ $vendorBill->invoice?->invoice_number ?? '—' }}</div>
                <div class="meta-row"><strong>Bill Date:</strong> {{ $billDate ? $billDate->format('d.m.Y') : '—' }}</div>
                <div class="meta-row"><strong>Due Date:</strong> {{ $dueDate ? $dueDate->format('d.m.Y') : '—' }}</div>
                <div class="meta-row"><strong>Terms:</strong> {{ $terms }}</div>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th class="ix">#</th>
                <th>Item &amp; Description</th>
                <th style="width:22%;">Customer Details</th>
                <th class="num" style="width:9%;">Qty</th>
                <th class="num" style="width:12%;">Rate</th>
                <th class="num" style="width:12%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="ix">1</td>
                <td>
                    <span class="item-title">{{ $vendorBill->service_type }}</span>
                    @if($descLines !== '')
                        {!! nl2br(e($descLines)) !!}
                    @endif
                </td>
                <td class="customer-details">
                    @if($lead)
                        <div>C/O {{ $lead->customer_name }}</div>
                        @if($lead->reference_id)
                            <div class="ref">{{ $lead->reference_id }}</div>
                        @endif
                    @else
                        —
                    @endif
                    @if($vendorBill->notes && stripos($vendorBill->notes, 'non-billable') !== false)
                        <div style="margin-top:6px;color:#6b7280;">Non-Billable</div>
                    @endif
                </td>
                <td class="num">{{ $fmt(1) }}</td>
                <td class="num">{{ $fmt($vendorBill->bill_amount) }}</td>
                <td class="num">{{ $fmt($vendorBill->bill_amount) }}</td>
            </tr>
        </tbody>
    </table>

    @php $amt = (float) $vendorBill->bill_amount; @endphp
    <table class="totals">
        <tr>
            <td>Sub Total</td>
            <td>{{ $fmt($amt) }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>LKR{{ $fmt($amt) }}</td>
        </tr>
        <tr class="balance">
            <td>Balance Due</td>
            <td>LKR{{ $fmt($amt) }}</td>
        </tr>
    </table>
</body>
</html>
