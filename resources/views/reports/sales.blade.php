<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #2d3748; color: white; padding: 8px; text-align: left; }
        td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f7fafc; }
        .summary { margin: 15px 0; padding: 10px; background: #ebf8ff; border-radius: 5px; }
        .summary table { margin: 0; }
        .summary td { border: none; padding: 4px 8px; }
        .text-right { text-align: right; }
        .badge-paid { color: green; font-weight: bold; }
        .badge-partial { color: orange; font-weight: bold; }
        .badge-unpaid { color: red; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; color: #999; font-size: 10px; }
    </style>
</head>
<body>

<div class="header">
    <h2>Sales Report</h2>
    <p>From: {{ $from }} &nbsp;&nbsp; To: {{ $to }}</p>
    <p>Generated: {{ now()->format('d M Y, h:i A') }}</p>
</div>

<div class="summary">
    <table>
        <tr>
            <td><strong>Total Sales:</strong></td>
            <td>৳ {{ number_format($totalAmount, 2) }}</td>
            <td><strong>Total Profit:</strong></td>
            <td>৳ {{ number_format($totalProfit, 2) }}</td>
            <td><strong>Total Invoices:</strong></td>
            <td>{{ $sales->count() }}</td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Customer</th>
            <th class="text-right">Amount</th>
            <th class="text-right">Paid</th>
            <th class="text-right">Due</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $i => $sale)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $sale->invoice_no }}</td>
            <td>{{ $sale->created_at->format('d/m/Y') }}</td>
            <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
            <td class="text-right">৳ {{ number_format($sale->total_amount, 2) }}</td>
            <td class="text-right">৳ {{ number_format($sale->paid_amount, 2) }}</td>
            <td class="text-right">৳ {{ number_format($sale->due_amount, 2) }}</td>
            <td>
                <span class="badge-{{ $sale->payment_status }}">
                    {{ strtoupper($sale->payment_status) }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center">কোনো sales পাওয়া যায়নি</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <p>Supershop ERP — Generated automatically</p>
</div>

</body>
</html>