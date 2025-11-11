{{-- resources/views/pdf/payment-slip.blade.php --}}
@php
    /* ===== Helpers ===== */
    function money_fmt($amount, $currency = 'GBP')
    {
        $symbolMap = ['BDT' => '৳', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹'];
        $symbol = $symbolMap[$currency] ?? $currency . ' ';
        return $symbol . number_format((float) $amount, 2);
    }
    function orgLogoSrc($logo)
    {
        if (!$logo) {
            return null;
        }
        if (preg_match('~^https?://~i', $logo)) {
            return $logo;
        }
        foreach (
            [
                public_path($logo),
                public_path('storage/' . ltrim($logo, '/')),
                storage_path('app/public/' . ltrim($logo, '/')),
                $logo,
            ]
            as $p
        ) {
            if (@is_file($p)) {
                return $p;
            }
        }
        return null;
    }
    function method_label($t)
    {
        if (!$t || $t === 'N/A') {
            return null;
        }
        $map = [
            'afterpay_clearpay' => 'Afterpay / Clearpay',
            'us_bank_account' => 'US Bank Account (ACH)',
            'sepa_debit' => 'SEPA Direct Debit',
            'bacs_debit' => 'Bacs Direct Debit',
            'wechat_pay' => 'WeChat Pay',
            'bank_transfer' => 'Bank Transfer',
        ];
        return $map[strtolower($t)] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $t));
    }

    /* ===== Lines & totals ===== */
    $lines = collect($courses ?? [])->map(function ($c) {
        $qty = (int) ($c['qty'] ?? 1);
        $unit = (float) ($c['unit_price'] ?? 0);
        $disc = (float) ($c['line_discount'] ?? 0);
        return array_merge($c, [
            'qty' => $qty,
            'unit_price' => $unit,
            'line_discount' => $disc,
            'line_total' => max($qty * $unit - $disc, 0),
        ]);
    });

    $currency = $payment['currency'] ?? 'GBP';
    $subtotal = $payment['subtotal'] ?? $lines->sum('line_total');
    $discountAmount = (float) ($payment['discount_amount'] ?? 0);
    $processingFee = (float) ($payment['processing_fee'] ?? 0);
    $grandTotal = max($subtotal - $discountAmount + $processingFee, 0);
    $amountPaid = $payment['amount_paid'] ?? $grandTotal;
    $balance = max($grandTotal - $amountPaid, 0);

    $statusColor =
        [
            'Paid' => '#16a34a',
            'Partially Paid' => '#f59e0b',
            'Pending' => '#2563eb',
            'Refunded' => '#e11d48',
            'Failed' => '#e11d48',
        ][$payment['status'] ?? 'Paid'] ?? '#2563eb';

    $accent = '#0ea5e9';
    $logoSrc = orgLogoSrc($org['logo'] ?? null);

    // Stripe-derived display fields (IDs/receipt intentionally hidden)
    $pmLabel = method_label($stripe['payment_method_type'] ?? null);
    $cardBrand = $stripe['card_brand'] ?? null;
    $cardLast4 = $stripe['card_last4'] ?? null;
    $cardExpM = $stripe['card_exp_month'] ?? null;
    $cardExpY = $stripe['card_exp_year'] ?? null;
    $cardFunding = $stripe['card_funding'] ?? null;
    $walletType = $stripe['wallet_type'] ?? null;
    $bankName = $stripe['bank_name'] ?? null;
    $bankLast4 = $stripe['bank_account_last4'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Payment Slip — {{ $payment['slip_no'] ?? 'N/A' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page {
            margin: 24mm 16mm 24mm 16mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        .header {
            display: table;
            width: 100%;
        }

        .header .logo,
        .header .identity {
            display: table-cell;
            vertical-align: middle;
        }

        .header .logo {
            width: 25%;
        }

        .header .identity {
            width: 75%;
            text-align: right;
        }

        .logo img {
            max-height: 56px;
        }

        .brand-line {
            height: 4px;
            background: {{ $accent }};
            border-radius: 4px;
            margin-top: 8px;
        }

        .title-row {
            margin: 16px 0 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .doc-title {
            display: table-cell;
            font-size: 22px;
            letter-spacing: .4px;
            font-weight: 800;
            color: {{ $accent }};
            vertical-align: middle;
            width: 70%;
        }

        .title-row .chip-cell {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            width: 30%;
        }

        .chip {
            display: inline-block;
            padding: 3px 8px;
            border: 1px solid #d1d5db;
            border-radius: 9999px;
            font-size: 10px;
            color: #374151;
        }

        .summary {
            margin-top: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            display: table;
            width: 100%;
            table-layout: fixed;
            background: #f9fafb;
        }

        .summary .item {
            display: table-cell;
            vertical-align: top;
            padding: 6px 10px;
        }

        .summary .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #6b7280;
        }

        .summary .value {
            font-size: 16px;
            font-weight: 800;
        }

        .summary .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 999px;
            color: #fff;
            background: {{ $statusColor }};
            font-size: 7px;
            font-weight: 600;
        }

        .grid {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-top: 14px;
        }

        .grid .col {
            display: table-cell;
            vertical-align: top;
            padding-right: 12px;
        }

        .grid .col:last-child {
            padding-right: 0;
        }

        .panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .panel h4 {
            margin: 0 0 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #6b7280;
        }

        .soft {
            color: #374151;
        }

        .kv {
            margin: 4px 0;
        }

        .kv .k {
            color: #6b7280;
            display: inline-block;
            min-width: 88px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .table thead th {
            text-align: left;
            font-size: 11px;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: #6b7280;
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .row-alt {
            background: #fafafa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #6b7280;
        }

        /* Totals: right side, key:value rows */
        .totals {
            margin-top: 16px;
            width: 45%;
            margin-left: auto;
        }

        .totals .row {
            display: table;
            width: 100%;
            table-layout: fixed;
            padding: 6px 0;
        }

        .totals .row .k {
            display: table-cell;
            color: #6b7280;
            text-align: left;
            width: 60%;
        }

        .totals .row>div:last-child {
            display: table-cell;
            text-align: right;
            width: 40%;
        }

        .totals .row.em {
            font-weight: 700;
            border-top: 2px solid #e5e7eb;
            padding-top: 10px;
            margin-top: 4px;
        }

        .totals .row.bd {
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }

        .notes {
            margin-top: 12px;
            font-size: 11px;
            color: #4b5563;
            border-left: 3px solid #e5e7eb;
            padding-left: 10px;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -10mm;
            height: 20mm;
            color: #6b7280;
            font-size: 10px;
        }

        .footer .inner {
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            display: table;
            width: 100%;
        }

        .footer .inner>div {
            display: table-cell;
        }

        .footer .inner>div:last-child {
            text-align: right;
        }

        .page-number:after {
            content: counter(page);
        }

        .watermark {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            font-size: 64px;
            color: rgba(107, 114, 128, 0.07);
            font-weight: 900;
            white-space: nowrap;
            z-index: -1;
        }

        /* Signatures row */
        .sigrow {
            margin-top: 28px;
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .sigrow .cell {
            display: table-cell;
            width: 50%;
            padding: 0 12px;
        }

        .sigrow .cell:first-child {
            padding-left: 0;
        }

        .sigrow .cell:last-child {
            padding-right: 0;
        }

        .sigrow .line {
            border-top: 1px solid #e5e7eb;
            height: 0;
            margin-top: 32px;
        }

        .sigrow .label {
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            margin-top: 6px;
        }
    </style>
</head>

<body>

    <div class="watermark">{{ strtoupper($payment['status'] ?? 'PAID') }}</div>

    <header class="header">
        <div class="logo">
            @if ($logoSrc)
                <img src="{{ $logoSrc }}">
            @else
                <div
                    style="display:inline-block;padding:12px 14px;border:1px solid #e5e7eb;border-radius:8px;font-weight:800;">
                    {{ strtoupper(collect(explode(' ', $org['name'] ?? 'Organization'))->map(fn($w) => mb_substr($w, 0, 1))->join('')) }}
                </div>
            @endif
        </div>
        <div class="identity">
            <div style="font-size:18px;font-weight:800;">{{ $org['name'] ?? 'Your Organization' }}</div>
            <div class="soft">{{ $org['address'] ?? '' }}</div>
            <div class="soft">{{ $org['phone'] ?? '' }} · {{ $org['email'] ?? '' }} · {{ $org['website'] ?? '' }}
            </div>
            <div class="brand-line"></div>
        </div>
    </header>

    <div class="title-row">
        <div class="doc-title">Payment Slip</div>
        <div class="chip-cell">
            <div class="chip">{{ $payment['slip_no'] ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="item" style="width:33.33%;">
            <div class="label">Amount</div>
            <div class="value">{{ money_fmt($amountPaid, $currency) }}</div>
        </div>
        <div class="item" style="width:33.33%;">
            <div class="label">Method</div>
            <div class="value">{{ $pmLabel ?? 'Card' }}</div>
            @if ($walletType)
                <div class="soft" style="font-size:10px;">Wallet: {{ $walletType }}</div>
            @endif
        </div>
        <div class="item" style="width:33.33%;">
            <div class="label">Status: <span class="badge">{{ $payment['status'] ?? 'Paid' }}</span></div>
            <div class="soft" style="font-size:10px;">
                {{ \Carbon\Carbon::parse($payment['date'] ?? now())->format('d M Y, h:i A') }}</div>
        </div>
    </div>

    <div class="grid" style="margin-top:14px;">
        <div class="col" style="width:50%;">
            <div class="panel">
                <h4>Bill To</h4>
                <div><strong>{{ $student['name'] ?? 'Student' }}</strong></div>
                <div class="soft">{{ $student['email'] ?? '' }}</div>
                <div class="soft">{{ $student['phone'] ?? '' }}</div>
            </div>
        </div>
        <div class="col" style="width:50%;">
            <div class="panel">
                <h4>Payment Details</h4>
                @if ($pmLabel)
                    <div class="kv"><span class="k">Method</span> <span>{{ $pmLabel }}</span></div>
                @endif
                @if ($cardBrand || $cardLast4)
                    <div class="kv"><span class="k">Card</span>
                        <span>{{ $cardBrand ?? 'Card' }} &nbsp;••••&nbsp;{{ $cardLast4 ?? 'XXXX' }}
                            @if ($cardExpM && $cardExpY)
                                ({{ str_pad($cardExpM, 2, '0', STR_PAD_LEFT) }}/{{ $cardExpY }})
                            @endif
                        </span>
                    </div>
                    @if ($cardFunding)
                        <div class="kv soft"><span class="k">Funding</span> <span>{{ $cardFunding }}</span></div>
                    @endif
                    @if ($walletType)
                        <div class="kv soft"><span class="k">Wallet</span> <span>{{ $walletType }}</span></div>
                    @endif
                @endif
                @if ($bankName)
                    <div class="kv"><span class="k">Bank</span> <span>{{ $bankName }}</span></div>
                    @if ($bankLast4)
                        <div class="kv soft"><span class="k">Account</span> <span>•••• {{ $bankLast4 }}</span>
                        </div>
                    @endif
                @endif
                @if (!empty($qr_base64 ?? null))
                    <div style="text-align:right;margin-top:8px;"><img src="{{ $qr_base64 }}" alt="QR"
                            style="max-width:100px;"></div>
                @endif
            </div>
        </div>
    </div>

    <table class="table" role="presentation" aria-hidden="true">
        <thead>
            <tr>
                <th>Course</th>
                <th class="text-center" style="width:8%;">Qty</th>
                <th class="text-right" style="width:14%;">Unit</th>
                <th class="text-right" style="width:14%;">Discount</th>
                <th class="text-right" style="width:16%;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $i => $line)
                <tr class="{{ $i % 2 ? 'row-alt' : '' }}">
                    <td><strong>{{ $line['title'] ?? 'Course' }}</strong></td>
                    <td class="text-center">{{ $line['qty'] }}</td>
                    <td class="text-right">{{ money_fmt($line['unit_price'], $currency) }}</td>
                    <td class="text-right">{{ money_fmt($line['line_discount'] ?? 0, $currency) }}</td>
                    <td class="text-right"><strong>{{ money_fmt($line['line_total'], $currency) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center muted">No course lines.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <div class="row bd">
            <div class="k">Subtotal:</div>
            <div>{{ money_fmt($subtotal, $currency) }}</div>
        </div>
        @if ($discountAmount > 0)
            <div class="row">
                <div class="k">Discount:</div>
                <div>-{{ money_fmt($discountAmount, $currency) }}</div>
            </div>
        @endif
        @if ($processingFee > 0)
            <div class="row">
                <div class="k">Processing Fee:</div>
                <div>{{ money_fmt($processingFee, $currency) }}</div>
            </div>
        @endif
        <div class="row em">
            <div class="k">Total:</div>
            <div>{{ money_fmt($grandTotal, $currency) }}</div>
        </div>
        <div class="row">
            <div class="k">Amount Paid:</div>
            <div>{{ money_fmt($amountPaid, $currency) }}</div>
        </div>
        @if ($balance > 0)
            <div class="row" style="font-weight:700;">
                <div class="k">Balance Due:</div>
                <div>{{ money_fmt($balance, $currency) }}</div>
            </div>
        @endif
    </div>

    <!-- Signature -->
    <div style="margin-top:28px; text-align:right;">
        <div style="display:inline-block; width:40%; text-align:center;">
            <div style="border-top:1px solid #e5e7eb; margin-top:32px;"></div>
            <div style="font-size:11px; color:#6b7280; margin-top:6px;">Accounts Office</div>
        </div>
    </div>

    @if (!empty($payment['notes']))
        <div class="notes" style="margin-top:20px;"><strong>Notes:</strong> {{ $payment['notes'] }}</div>
    @endif

    <footer class="footer">
        <div class="inner">
            <div>Generated on {{ now()->format('d M Y, h:i A') }} · {{ $org['website'] ?? '' }}</div>
        </div>
    </footer>

</body>

</html>
