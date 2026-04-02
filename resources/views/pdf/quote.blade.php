@php
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
    $lines = $quote->lineItems;
    $total = (float) $quote->totalAmount();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quote {{ $quote->quote_number }}</title>
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
        table.banner { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.banner td { vertical-align: top; padding: 0 12px 0 0; }
        table.banner td.right-block { text-align: right; padding-right: 0; }
        .company-name { font-size: 13px; font-weight: bold; margin-bottom: 6px; }
        .doc-title { font-size: 17px; font-weight: bold; margin-bottom: 4px; }
        .meta-row { margin-bottom: 3px; }
        .bill-to-title { font-weight: bold; margin-bottom: 4px; font-size: 11px; }
        .bill-to-name { font-weight: 600; }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
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
        table.totals tr.grand td {
            background: #f3f4f6;
            font-weight: bold;
        }
        .bank {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #444;
        }
        .notice {
            margin-top: 14px;
            font-size: 9px;
            color: #555;
            font-style: italic;
        }
    </style>
</head>
<body>
    <table class="banner">
        <tr>
            <td width="50%">
                <div class="company-name">{{ $company['name'] }}</div>
                <div>{!! nl2br(e($company['address'])) !!}</div>
                <div>{{ $company['country'] }}</div>
                <div>{{ $company['phone'] }}</div>
                <div>{{ $company['email'] }}</div>
                <div style="margin-top:14px;" class="bill-to-title">Quote For</div>
                <div class="bill-to-name">{{ $quote->lead?->customer_name ?? '—' }}</div>
                @if($quote->lead?->reference_id)
                    <div style="margin-top:4px;color:#555;">{{ $quote->lead->reference_id }}</div>
                @endif
            </td>
            <td class="right-block" width="50%">
                <div class="doc-title">Quotation</div>
                <div style="margin-bottom:8px;"><strong># {{ $quote->quote_number }}</strong></div>
                <div class="meta-row"><strong>Quote Date:</strong> {{ optional($quote->quote_date)->format('d.m.Y') ?? '—' }}</div>
                <div class="meta-row"><strong>Valid Until:</strong> {{ optional($quote->valid_until)->format('d.m.Y') ?? '—' }}</div>
                @if($quote->terms)
                    <div class="meta-row"><strong>Terms:</strong> {{ $quote->terms }}</div>
                @endif
            </td>
        </tr>
    </table>

    @if($quote->subject)
        <div style="margin-bottom:10px;"><strong>Subject:</strong> {{ $quote->subject }}</div>
    @endif

    <table class="lines">
        <thead>
            <tr>
                <th class="ix">#</th>
                <th>Item &amp; Description</th>
                <th class="num" style="width:11%;">Qty</th>
                <th class="num" style="width:14%;">Rate (LKR)</th>
                <th class="num" style="width:14%;">Amount (LKR)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $i => $line)
                @php
                    $raw = trim((string) $line->description);
                    $nl = strpos($raw, "\n");
                    $title = $nl !== false ? substr($raw, 0, $nl) : $raw;
                    $body = $nl !== false ? trim(substr($raw, $nl + 1)) : '';
                @endphp
                <tr>
                    <td class="ix">{{ $i + 1 }}</td>
                    <td>
                        @if($body !== '')
                            <span class="item-title">{{ $title }}</span>
                            {!! nl2br(e($body)) !!}
                        @else
                            {!! nl2br(e($title)) !!}
                        @endif
                    </td>
                    <td class="num">{{ $fmt($line->quantity) }}</td>
                    <td class="num">{{ $fmt($line->rate) }}</td>
                    <td class="num">{{ $fmt($line->amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="ix">1</td>
                    <td colspan="4" style="color:#6b7280;">No line items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Sub Total</td>
            <td>{{ $fmt($total) }}</td>
        </tr>
        <tr class="grand">
            <td>Total (LKR)</td>
            <td>{{ $fmt($total) }}</td>
        </tr>
    </table>

    @if($quote->notes)
        <div style="margin-top:16px;"><strong>Notes</strong></div>
        <div>{!! nl2br(e($quote->notes)) !!}</div>
    @endif

    <p class="notice">This document is a quotation only and does not constitute a tax invoice.</p>

    <div class="bank">
        <strong>Bank Details</strong> (for payment upon acceptance)<br>
        Account Name: {{ $company['bank']['account_name'] }}<br>
        Account Number: {{ $company['bank']['account_number'] }}<br>
        Bank: {{ $company['bank']['bank'] }}<br>
        Branch: {{ $company['bank']['branch'] }}<br>
        SWIFT/BIC: {{ $company['bank']['swift'] }}<br>
        Please include the quote number in the payment reference.
    </div>
</body>
</html>
