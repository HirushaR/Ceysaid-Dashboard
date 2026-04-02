@php
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
    $lines = $quote->lineItems;
    $total = (float) $quote->totalAmount();
    $terms = $quote->terms ?: 'Due on Receipt';
    $billTo = $quote->lead?->customer_name ?? '—';
    $logoSrc = isset($logoPath) && $logoPath !== '' ? str_replace('\\', '/', $logoPath) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quote {{ $quote->quote_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #222;
            margin: 0;
            padding: 28px 32px;
            line-height: 1.5;
            background: #fff;
        }
        .top-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .top-table td { vertical-align: top; padding: 0; }
        .top-table td.meta { text-align: right; width: 42%; }
        .logo-wrap { margin-bottom: 10px; }
        .logo-wrap img {
            max-height: 48px;
            width: auto;
            max-width: 220px;
            display: block;
        }
        .doc-title {
            font-size: 26px;
            font-weight: bold;
            color: #1f3c88;
            margin: 0 0 8px 0;
        }
        .company-details p,
        .invoice-meta p,
        .bill-to p,
        .notes p,
        .bank-details p {
            margin: 3px 0;
            line-height: 1.5;
        }
        .company-details strong { font-size: 11px; }
        .highlight-box {
            margin-top: 14px;
            background: #f1f5ff;
            border-left: 5px solid #1f3c88;
            padding: 12px 16px;
            border-radius: 4px;
        }
        .highlight-box h2 {
            margin: 0 0 6px 0;
            font-size: 16px;
            color: #1a1a1a;
        }
        .section { margin-top: 22px; }
        .section h3 {
            margin: 0 0 10px 0;
            color: #1f3c88;
            font-size: 12px;
            border-bottom: 2px solid #eee;
            padding-bottom: 6px;
        }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.lines th,
        table.lines td {
            border: 1px solid #ddd;
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
        }
        table.lines th {
            background: #f4f6fb;
            font-weight: bold;
        }
        table.lines th.num, table.lines td.num { text-align: right; white-space: nowrap; }
        table.lines th.ix, table.lines td.ix { width: 28px; text-align: center; }
        .item-title { font-weight: bold; display: block; margin-bottom: 3px; }
        table.totals {
            margin-top: 20px;
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }
        table.totals td {
            padding: 10px 14px;
            border-bottom: 1px solid #ddd;
        }
        table.totals tr:last-child td {
            border-bottom: none;
            background: #1f3c88;
            color: #fff;
            font-weight: bold;
        }
        table.totals td:last-child { text-align: right; }
        .footer-note {
            margin-top: 22px;
            font-size: 9px;
            color: #444;
            background: #fff8e8;
            border-left: 4px solid #f0b429;
            padding: 10px 12px;
            border-radius: 4px;
        }
        .quotation-notice {
            margin-top: 14px;
            font-size: 9px;
            color: #555;
            font-style: italic;
        }
    </style>
</head>
<body>
    <table class="top-table">
        <tr>
            <td>
                @if($logoSrc)
                    <div class="logo-wrap">
                        <img src="{{ $logoSrc }}" alt="">
                    </div>
                @endif
                <div class="company-details">
                    <div class="doc-title">Quotation</div>
                    <p><strong>{{ $company['name'] }}</strong></p>
                    @foreach(preg_split("/\r\n|\n|\r/", (string) $company['address']) as $line)
                        @if(trim($line) !== '')
                            <p>{{ trim($line) }}</p>
                        @endif
                    @endforeach
                    <p>{{ $company['country'] }}</p>
                    <p>{{ $company['phone'] }}</p>
                    <p>{{ $company['email'] }}</p>
                </div>
            </td>
            <td class="meta">
                <div class="invoice-meta">
                    <p><strong>Quote #:</strong> {{ $quote->quote_number }}</p>
                    <p><strong>Quote Date:</strong> {{ optional($quote->quote_date)->format('d.m.Y') ?? '—' }}</p>
                    <p><strong>Terms:</strong> {{ $terms }}</p>
                    <p><strong>Valid Until:</strong> {{ optional($quote->valid_until)->format('d.m.Y') ?? '—' }}</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="highlight-box">
        <h2>Total: LKR {{ $fmt($total) }}</h2>
        @if($quote->subject)
            <p><strong>Subject:</strong> {{ $quote->subject }}</p>
        @endif
    </div>

    <div class="section bill-to">
        <h3>Bill To</h3>
        <p>{{ $billTo }}</p>
        @if($quote->lead?->reference_id)
            <p>{{ $quote->lead->reference_id }}</p>
        @endif
    </div>

    <div class="section">
        <h3>Quote Items</h3>
        <table class="lines">
            <thead>
                <tr>
                    <th class="ix">#</th>
                    <th>Item &amp; Description</th>
                    <th class="num" style="width:11%;">Qty</th>
                    <th class="num" style="width:14%;">Rate</th>
                    <th class="num" style="width:14%;">Amount</th>
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
                        <td class="num">LKR {{ $fmt($line->rate) }}</td>
                        <td class="num">LKR {{ $fmt($line->amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="ix">1</td>
                        <td colspan="4" style="color:#666;">No line items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <table class="totals">
        <tr>
            <td>Sub Total</td>
            <td>LKR {{ $fmt($total) }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>LKR {{ $fmt($total) }}</td>
        </tr>
        <tr>
            <td>Grand Total</td>
            <td>LKR {{ $fmt($total) }}</td>
        </tr>
    </table>

    @if($quote->notes)
        <div class="section notes">
            <h3>Notes</h3>
            <p>{!! nl2br(e($quote->notes)) !!}</p>
        </div>
    @endif

    <div class="section bank-details">
        <h3>Bank Details</h3>
        <p><strong>Account Name:</strong> {{ $company['bank']['account_name'] }}</p>
        <p><strong>Account Number:</strong> {{ $company['bank']['account_number'] }}</p>
        <p><strong>Bank:</strong> {{ $company['bank']['bank'] }}</p>
        <p><strong>Branch:</strong> {{ $company['bank']['branch'] }}</p>
        <p><strong>Code:</strong> {{ $company['bank']['branch_code'] ?? '—' }}</p>
        <p><strong>SWIFT/BIC Code:</strong> {{ $company['bank']['swift'] }}</p>
    </div>

    <div class="footer-note">
        PLEASE INCLUDE THE QUOTE NUMBER IN THE PAYMENT REFERENCE WHEN MAKING THE PAYMENT.
        <br><br>
        ALL PAYMENTS TO BE MADE PAYABLE TO {{ strtoupper($company['name']) }}.
    </div>

    <p class="quotation-notice">This document is a quotation only and does not constitute a tax invoice.</p>
</body>
</html>
