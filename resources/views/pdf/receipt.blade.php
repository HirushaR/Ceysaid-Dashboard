@php
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
    $lines = $invoice->lineItems;
    $invTotal = (float) $invoice->total_amount;
    $payAmount = (float) $payment->amount;
    $terms = $invoice->terms ?: 'Due on Receipt';
    $billTo = $invoice->lead?->customer_name ?? '—';
    $logoSrc = isset($logoPath) && $logoPath !== '' ? str_replace('\\', '/', $logoPath) : null;
    $modeLabel = \App\Enums\PaymentMode::tryFrom((string) $payment->payment_method)?->label() ?? (string) $payment->payment_method;
    $depositLabel = \App\Enums\DepositAccount::tryFrom((string) $payment->deposit_to)?->label() ?? (string) $payment->deposit_to;
    $receiptNo = $payment->receipt_number ?: '—';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receiptNo }}</title>
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
                    <div class="doc-title">Payment receipt</div>
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
                    <p><strong>Receipt #:</strong> {{ $receiptNo }}</p>
                    <p><strong>Receipt Date:</strong> {{ optional($payment->payment_date)->format('d.m.Y') ?? '—' }}</p>
                    <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                    <p><strong>Invoice Date:</strong> {{ optional($invoice->invoice_date)->format('d.m.Y') ?? '—' }}</p>
                    <p><strong>Terms:</strong> {{ $terms }}</p>
                    <p><strong>Payment mode:</strong> {{ $modeLabel }}</p>
                    <p><strong>Deposit to:</strong> {{ $depositLabel }}</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="highlight-box">
        <h2>Amount received: LKR {{ $fmt($payAmount) }}</h2>
        @if($invoice->subject)
            <p><strong>Subject:</strong> {{ $invoice->subject }}</p>
        @endif
    </div>

    <div class="section bill-to">
        <h3>Received from</h3>
        <p>{{ $billTo }}</p>
        @if($invoice->lead?->reference_id)
            <p>{{ $invoice->lead->reference_id }}</p>
        @endif
    </div>

    <div class="section">
        <h3>Invoice items</h3>
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
                        <td class="num">{{ $fmt(1) }}</td>
                        <td class="num">LKR {{ $fmt($invTotal) }}</td>
                        <td class="num">LKR {{ $fmt($invTotal) }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <table class="totals">
        <tr>
            <td>Invoice total</td>
            <td>LKR {{ $fmt($invTotal) }}</td>
        </tr>
        <tr>
            <td>Payment on this receipt</td>
            <td>LKR {{ $fmt($payAmount) }}</td>
        </tr>
        <tr>
            <td>Amount received</td>
            <td>LKR {{ $fmt($payAmount) }}</td>
        </tr>
    </table>

    @if($payment->notes)
        <div class="section notes">
            <h3>Notes</h3>
            <p>{!! nl2br(e($payment->notes)) !!}</p>
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
        PLEASE INCLUDE THE INVOICE NUMBER IN THE PAYMENT REFERENCE FOR ANY FURTHER PAYMENTS.
        <br><br>
        ALL PAYMENTS TO BE MADE PAYABLE TO {{ strtoupper($company['name']) }}.
    </div>

    <p class="quotation-notice">This receipt acknowledges payment received and is not a replacement tax invoice.</p>
</body>
</html>
