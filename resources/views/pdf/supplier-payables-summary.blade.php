@php
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
    $logoSrc = isset($logoPath) && $logoPath !== '' ? str_replace('\\', '/', $logoPath) : null;
    $grandTotal = $suppliers->sum(fn ($s) => (float) ($s->payable_amount ?? $s->totalOutstandingPayable()));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Supplier payables summary</title>
    @include('pdf.partials.ceysaid-pdf-theme')
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
                    <div class="doc-title">Supplier payables</div>
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
                    <p><strong>Report:</strong> All suppliers</p>
                    <p><strong>Generated:</strong> {{ now()->format('d.m.Y H:i') }}</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="highlight-box">
        <h2>Combined total to pay: LKR {{ $fmt($grandTotal) }}</h2>
        <p>Sum of outstanding balances across all suppliers (ordered by amount due).</p>
    </div>

    <div class="section">
        <h3>Suppliers</h3>
        <table class="lines">
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contact</th>
                    <th class="num" style="width:18%;">Total to pay</th>
                    <th class="num" style="width:12%;">Bills</th>
                </tr>
            </thead>
            <tbody>
                @foreach($suppliers as $s)
                    <tr>
                        <td><strong>{{ $s->name }}</strong></td>
                        <td>{{ $s->contact_name ?: '—' }}</td>
                        <td class="num">LKR {{ $fmt((float) ($s->payable_amount ?? $s->totalOutstandingPayable())) }}</td>
                        <td class="num">{{ $s->vendor_bills_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="totals">
        <tr>
            <td>Suppliers listed</td>
            <td>{{ $suppliers->count() }}</td>
        </tr>
        <tr>
            <td>Grand total to pay</td>
            <td>LKR {{ $fmt($grandTotal) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        Downloaded from TravelSync supplier payables. Amounts reflect vendor bill totals minus recorded payments.
    </div>

    <p class="doc-notice">For internal use by finance and administration.</p>
</body>
</html>
