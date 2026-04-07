@php
    $lead = $vendorBill->invoice?->lead;
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
    $descLines = trim((string) ($vendorBill->service_details ?? ''));
    $billDate = $vendorBill->created_at;
    $dueDate = $vendorBill->due_date ?? $vendorBill->invoice?->due_date;
    $terms = $vendorBill->invoice?->terms ?: 'Due on Receipt';
    $total = (float) $vendorBill->bill_amount;
    $billTo = $lead?->customer_name ?? '—';

    $itemBlock = trim((string) ($vendorBill->service_type ?? 'Service'));
    if ($descLines !== '') {
        $itemBlock .= "\n".$descLines;
    }
    if ($lead) {
        $custLine = 'Customer: '.$lead->customer_name;
        if ($lead->reference_id) {
            $custLine .= ' · Ref: '.$lead->reference_id;
        }
        $itemBlock .= "\n".$custLine;
    }
    $nl = strpos($itemBlock, "\n");
    $lineTitle = $nl !== false ? substr($itemBlock, 0, $nl) : $itemBlock;
    $lineBody = $nl !== false ? trim(substr($itemBlock, $nl + 1)) : '';

    $logoSrc = isset($logoPath) && $logoPath !== '' ? str_replace('\\', '/', $logoPath) : null;
    $paymentStatus = ucfirst((string) ($vendorBill->payment_status ?? 'pending'));
    $payments = $vendorBill->relationLoaded('vendorBillPayments') ? $vendorBill->vendorBillPayments : $vendorBill->vendorBillPayments()->orderByDesc('payment_date')->orderByDesc('id')->get();
    $paidSum = (float) $payments->sum('amount');
    $outstanding = max(0, $total - $paidSum);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vendor bill {{ $vendorBill->vendor_bill_number }}</title>
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
        .bank-details p,
        .vendor-from p {
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
        .doc-footer-notice {
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
                    <div class="doc-title">Vendor Bill</div>
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
                    <p><strong>Vendor Bill #:</strong> {{ $vendorBill->vendor_bill_number }}</p>
                    <p><strong>Invoice #:</strong> {{ $vendorBill->invoice?->invoice_number ?? '—' }}</p>
                    <p><strong>Bill Date:</strong> {{ $billDate ? $billDate->format('d.m.Y') : '—' }}</p>
                    <p><strong>Due Date:</strong> {{ $dueDate ? $dueDate->format('d.m.Y') : '—' }}</p>
                    <p><strong>Terms:</strong> {{ $terms }}</p>
                    <p><strong>Payment status:</strong> {{ $paymentStatus }}</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="highlight-box">
        <h2>Bill total: LKR {{ $fmt($total) }}</h2>
        <p><strong>Paid to date:</strong> LKR {{ $fmt($paidSum) }} &nbsp;|&nbsp; <strong>Balance:</strong> LKR {{ $fmt($outstanding) }}</p>
        <p><strong>Supplier:</strong> {{ $vendorBill->supplier?->name ?? $vendorBill->vendor_name }}</p>
    </div>

    <div class="section vendor-from">
        <h3>Bill From (Supplier)</h3>
        <p><strong>{{ $vendorBill->supplier?->name ?? $vendorBill->vendor_name }}</strong></p>
        @if($vendorBill->supplier?->address)
            @foreach(preg_split("/\r\n|\n|\r/", (string) $vendorBill->supplier->address) as $line)
                @if(trim($line) !== '')
                    <p>{{ trim($line) }}</p>
                @endif
            @endforeach
        @endif
    </div>

    <div class="section bill-to">
        <h3>Bill To / Customer on booking</h3>
        <p>{{ $billTo }}</p>
        @if($lead?->reference_id)
            <p>{{ $lead->reference_id }}</p>
        @endif
    </div>

    <div class="section">
        <h3>Bill Items</h3>
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
                <tr>
                    <td class="ix">1</td>
                    <td>
                        @if($lineBody !== '')
                            <span class="item-title">{{ $lineTitle }}</span>
                            {!! nl2br(e($lineBody)) !!}
                        @else
                            {!! nl2br(e($lineTitle)) !!}
                        @endif
                        @if($vendorBill->notes && stripos($vendorBill->notes, 'non-billable') !== false)
                            <p style="margin:8px 0 0 0;color:#666;font-size:9px;">Non-Billable</p>
                        @endif
                    </td>
                    <td class="num">{{ $fmt(1) }}</td>
                    <td class="num">LKR {{ $fmt($total) }}</td>
                    <td class="num">LKR {{ $fmt($total) }}</td>
                </tr>
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

    @if($payments->isNotEmpty())
        <div class="section">
            <h3>Payments recorded</h3>
            <table class="lines">
                <thead>
                    <tr>
                        <th class="ix">#</th>
                        <th>Date</th>
                        <th class="num">Amount</th>
                        <th>Mode</th>
                        <th>Account</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $i => $p)
                        <tr>
                            <td class="ix">{{ $i + 1 }}</td>
                            <td>{{ $p->payment_date?->format('d.m.Y') ?? '—' }}</td>
                            <td class="num">LKR {{ $fmt($p->amount) }}</td>
                            <td>{{ \App\Enums\PaymentMode::tryFrom((string) $p->payment_mode)?->label() ?? $p->payment_mode }}</td>
                            <td>{{ \App\Enums\DepositAccount::tryFrom((string) $p->paid_through)?->label() ?? $p->paid_through }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($vendorBill->notes)
        <div class="section notes">
            <h3>Notes</h3>
            <p>{!! nl2br(e($vendorBill->notes)) !!}</p>
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
        PLEASE INCLUDE THE VENDOR BILL NUMBER ({{ $vendorBill->vendor_bill_number }}) IN THE PAYMENT REFERENCE WHEN MAKING PAYMENT TO THE SUPPLIER OR FOR INTERNAL RECORDS.
        <br><br>
        ALL PAYMENTS TO BE MADE PAYABLE TO {{ strtoupper($company['name']) }} WHERE APPLICABLE.
    </div>

    <p class="doc-footer-notice">This document summarizes a supplier cost recorded against invoice {{ $vendorBill->invoice?->invoice_number ?? '—' }} and is for internal use unless issued by the supplier as their tax invoice.</p>
</body>
</html>
