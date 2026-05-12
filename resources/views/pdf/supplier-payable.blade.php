@php
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
    $fmtInOut = function (?float $v) use ($fmt) {
        if ($v === null || abs($v) < 0.00001) {
            return '—';
        }

        return 'LKR '.$fmt($v);
    };
    $logoSrc = isset($logoPath) && $logoPath !== '' ? str_replace('\\', '/', $logoPath) : null;
    $totalPayable = $supplier->totalOutstandingPayable();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Supplier payable — {{ $supplier->name }}</title>
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
                    <div class="doc-title">Supplier payable</div>
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
                    <p><strong>Supplier:</strong> {{ $supplier->name }}</p>
                    <p><strong>Generated:</strong> {{ now()->format('d.m.Y H:i') }}</p>
                    @if($supplier->contact_name)
                        <p><strong>Contact:</strong> {{ $supplier->contact_name }}</p>
                    @endif
                    @if($supplier->phone)
                        <p><strong>Phone:</strong> {{ $supplier->phone }}</p>
                    @endif
                    @if($supplier->email)
                        <p><strong>Email:</strong> {{ $supplier->email }}</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="highlight-box">
        <h2>Closing balance (to pay): LKR {{ $fmt($totalPayable) }}</h2>
        <p>Bank book: vendor bills (In) and payments (Out) in date order.</p>
    </div>

    @if($supplier->bank_details)
        <div class="section bank-details">
            <h3>Supplier bank details</h3>
            <p>{!! nl2br(e($supplier->bank_details)) !!}</p>
        </div>
    @endif

    <div class="section">
        <h3>Transaction history (bank book)</h3>
        <table class="lines">
            <thead>
                <tr>
                    <th class="num" style="width:12%;">Date</th>
                    <th>Description</th>
                    <th class="num" style="width:14%;">In</th>
                    <th class="num" style="width:14%;">Out</th>
                    <th class="num" style="width:14%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bankBookRows as $row)
                    <tr>
                        <td class="num">{{ isset($row['date']) && $row['date'] ? $row['date']->format('d.m.Y') : '—' }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td class="num">{{ $fmtInOut($row['in'] ?? null) }}</td>
                        <td class="num">{{ $fmtInOut($row['out'] ?? null) }}</td>
                        <td class="num"><strong>LKR {{ $fmt($row['balance']) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="color:#666;">No transactions.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer-note">
        In = vendor bill total recorded; Out = payment to supplier. Balance is running amount owed before the next transaction.
    </div>

    <p class="doc-notice">Figures are taken from vendor bills and payment entries in TravelSync.</p>
</body>
</html>
