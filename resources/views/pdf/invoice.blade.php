@php
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
        .inv-title { font-size: 17px; font-weight: bold; margin-bottom: 4px; }
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
        table.totals tr.balance td {
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
                <div style="margin-top:14px;" class="bill-to-title">Bill To</div>
                <div class="bill-to-name">{{ $invoice->lead?->customer_name ?? '—' }}</div>
                @if($invoice->lead?->reference_id)
                    <div style="margin-top:4px;color:#555;">{{ $invoice->lead->reference_id }}</div>
                @endif
            </td>
            <td class="right-block" width="50%">
                <div class="inv-title">Invoice</div>
                <div style="margin-bottom:8px;"><strong># {{ $invoice->invoice_number }}</strong></div>
                <div class="meta-row"><strong>Order Number:</strong> {{ $invoice->invoice_number }}</div>
                <div class="meta-row"><strong>Invoice Date:</strong> {{ optional($invoice->invoice_date)->format('d.m.Y') ?? '—' }}</div>
                <div class="meta-row"><strong>Due Date:</strong> {{ optional($invoice->due_date)->format('d.m.Y') ?? '—' }}</div>
                <div class="meta-row"><strong>Terms:</strong> {{ $invoice->terms ?? 'Due on Receipt' }}</div>
                <div class="meta-row" style="margin-top:8px;"><strong>Balance Due</strong> LKR{{ $fmt($invoice->customer_balance_amount) }}</div>
            </td>
        </tr>
    </table>

    @if($invoice->subject)
        <div style="margin-bottom:10px;"><strong>Subject:</strong> {{ $invoice->subject }}</div>
    @endif

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
            @php
                $lines = $invoice->lineItems;
                $hasLines = $lines->isNotEmpty();
            @endphp
            @if($hasLines)
                @foreach($lines as $i => $line)
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
                        <td>{!! nl2br(e($line->customer_details ?: '—')) !!}</td>
                        <td class="num">{{ $fmt($line->quantity) }}</td>
                        <td class="num">{{ $fmt($line->rate) }}</td>
                        <td class="num">{{ $fmt($line->amount) }}</td>
                    </tr>
                @endforeach
            @else
                @php
                    $d = trim((string) ($invoice->description ?? ''));
                    $subj = trim((string) ($invoice->subject ?? ''));
                    if ($subj !== '' && $d !== '') {
                        $fallbackDesc = $subj."\n".$d;
                    } elseif ($d !== '') {
                        $fallbackDesc = $d;
                    } elseif ($subj !== '') {
                        $fallbackDesc = $subj;
                    } else {
                        $fallbackDesc = 'Invoice total';
                    }
                    $ld = $invoice->lead;
                    $custFallback = '—';
                    if ($ld) {
                        $custFallback = 'C/O '.$ld->customer_name;
                        if ($ld->reference_id) {
                            $custFallback .= "\n".$ld->reference_id;
                        }
                    }
                    $tot = (float) $invoice->total_amount;
                    $fNl = strpos($fallbackDesc, "\n");
                    $fTitle = $fNl !== false ? substr($fallbackDesc, 0, $fNl) : $fallbackDesc;
                    $fBody = $fNl !== false ? trim(substr($fallbackDesc, $fNl + 1)) : '';
                @endphp
                <tr>
                    <td class="ix">1</td>
                    <td>
                        @if($fBody !== '')
                            <span class="item-title">{{ $fTitle }}</span>
                            {!! nl2br(e($fBody)) !!}
                        @else
                            {!! nl2br(e($fTitle)) !!}
                        @endif
                    </td>
                    <td>{!! nl2br(e($custFallback)) !!}</td>
                    <td class="num">{{ $fmt(1) }}</td>
                    <td class="num">{{ $fmt($tot) }}</td>
                    <td class="num">{{ $fmt($tot) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @php
        $sub = (float) $invoice->total_amount;
        $bal = (float) $invoice->customer_balance_amount;
    @endphp
    <table class="totals">
        <tr>
            <td>Sub Total</td>
            <td>{{ $fmt($sub) }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>LKR{{ $fmt($sub) }}</td>
        </tr>
        <tr class="balance">
            <td>Balance Due</td>
            <td>LKR{{ $fmt($bal) }}</td>
        </tr>
    </table>

    @if($invoice->notes)
        <div style="margin-top:16px;"><strong>Notes</strong></div>
        <div>{!! nl2br(e($invoice->notes)) !!}</div>
    @endif

    <div class="bank">
        <strong>Bank Details</strong><br>
        Account Name: {{ $company['bank']['account_name'] }}<br>
        Account Number: {{ $company['bank']['account_number'] }}<br>
        Bank: {{ $company['bank']['bank'] }}<br>
        Branch: {{ $company['bank']['branch'] }}<br>
        SWIFT/BIC: {{ $company['bank']['swift'] }}<br>
        Please include the invoice number in the payment reference.
    </div>
</body>
</html>
