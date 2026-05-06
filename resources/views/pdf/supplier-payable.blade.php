@php
    use App\Enums\DepositAccount;
    use App\Enums\PaymentMode;

    $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
    $logoSrc = isset($logoPath) && $logoPath !== '' ? str_replace('\\', '/', $logoPath) : null;
    $totalPayable = $supplier->totalOutstandingPayable();
    $bankLabel = fn (?string $v) => $v ? (DepositAccount::tryFrom($v)?->label() ?? ucfirst(str_replace('_', ' ', $v))) : '—';
    $modeLabel = fn (?string $v) => $v ? (PaymentMode::tryFrom($v)?->label() ?? $v) : '—';
    $leadLabel = function ($lead) {
        if (! $lead) {
            return '—';
        }
        $ref = $lead->reference_id ?: '#'.$lead->id;

        return $ref.' — '.$lead->customer_name;
    };
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
        <h2>Total to pay: LKR {{ $fmt($totalPayable) }}</h2>
        <p>Outstanding vendor bill balances for this supplier.</p>
    </div>

    @if($supplier->bank_details)
        <div class="section bank-details">
            <h3>Supplier bank details</h3>
            <p>{!! nl2br(e($supplier->bank_details)) !!}</p>
        </div>
    @endif

    <div class="section">
        <h3>What you owe (by lead)</h3>
        <table class="lines">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th class="num" style="width:14%;">Pay by</th>
                    <th class="num" style="width:16%;">Amount to pay</th>
                    <th style="width:18%;">Bill #</th>
                    <th style="width:12%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($openBills as $bill)
                    @php
                        $lead = $bill->invoice?->lead;
                    @endphp
                    <tr>
                        <td>{{ $leadLabel($lead) }}</td>
                        <td class="num">{{ $bill->due_date?->format('d.m.Y') ?? '—' }}</td>
                        <td class="num">LKR {{ $fmt($bill->outstanding_amount) }}</td>
                        <td>{{ $bill->vendor_bill_number }}</td>
                        <td>{{ ucfirst((string) $bill->payment_status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="color:#666;">No outstanding bills.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Payment history</h3>
        <table class="lines">
            <thead>
                <tr>
                    <th class="num" style="width:12%;">Date</th>
                    <th>Lead</th>
                    <th class="num" style="width:14%;">Amount</th>
                    <th style="width:18%;">Bank / account</th>
                    <th style="width:14%;">Mode</th>
                    <th style="width:14%;">Bill #</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $pLead = $payment->vendorBill?->invoice?->lead;
                    @endphp
                    <tr>
                        <td class="num">{{ optional($payment->payment_date)->format('d.m.Y') ?? '—' }}</td>
                        <td>{{ $leadLabel($pLead) }}</td>
                        <td class="num">LKR {{ $fmt($payment->amount) }}</td>
                        <td>{{ $bankLabel($payment->paid_through) }}</td>
                        <td>{{ $modeLabel($payment->payment_mode) }}</td>
                        <td>{{ $payment->vendorBill?->vendor_bill_number ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="color:#666;">No payments recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer-note">
        This report lists supplier payables and recorded vendor payments for internal finance use.
    </div>

    <p class="doc-notice">Figures are taken from vendor bills and payment entries in TravelSync.</p>
</body>
</html>
