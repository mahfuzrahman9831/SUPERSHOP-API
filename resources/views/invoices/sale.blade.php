<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <style>
        body        { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header     { text-align: center; margin-bottom: 20px; }
        .header h2  { margin: 0; font-size: 18px; }
        .header p   { margin: 2px 0; font-size: 11px; }
        table       { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td      { border: 1px solid #ddd; padding: 6px 8px; }
        th          { background: #f5f5f5; text-align: left; }
        .text-right { text-align: right; }
        .totals     { margin-top: 10px; float: right; width: 280px; }
        .totals td  { border: none; padding: 3px 8px; }
        .grand      { font-weight: bold; font-size: 14px; border-top: 2px solid #333; }
        .footer     { margin-top: 40px; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>

<div class="header">
    <h2>{{ setting('shop_name', 'SuperShop') }}</h2>
    <p>{{ setting('shop_address', '') }}</p>
    <p>{{ setting('shop_phone', '') }}</p>
    <hr>
    <h3 style="margin:5px 0">INVOICE</h3>
</div>

<table style="border:none; margin-bottom:10px">
    <tr>
        <td style="border:none"><strong>Invoice #:</strong> {{ $sale->invoice_number }}</td>
        <td style="border:none"><strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y, h:i A') }}</td>
    </tr>
    <tr>
        <td style="border:none">
            <strong>Customer:</strong>
            {{ $sale->customer?->name ?? 'Walk-in Customer' }}
            @if($sale->customer?->phone)
                ({{ $sale->customer->phone }})
            @endif
        </td>
        <td style="border:none"><strong>Cashier:</strong> {{ $sale->user?->name }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Price</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                {{ $item->product?->name }}
                @if($item->variant) <small>({{ $item->variant->name }})</small> @endif
            </td>
            <td class="text-right">{{ $item->quantity }}</td>
            <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
            <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <table>
        <tr><td>Subtotal</td><td class="text-right">{{ number_format($sale->subtotal, 2) }}</td></tr>
        @if($sale->tax_amount > 0)
        <tr><td>Tax</td><td class="text-right">{{ number_format($sale->tax_amount, 2) }}</td></tr>
        @endif
        @if($sale->discount_amount > 0)
        <tr><td>Discount</td><td class="text-right">- {{ number_format($sale->discount_amount, 2) }}</td></tr>
        @endif
        <tr class="grand">
            <td>Grand Total</td>
            <td class="text-right">{{ setting('shop_currency', 'BDT') }} {{ number_format($sale->grand_total, 2) }}</td>
        </tr>
        <tr><td>Paid</td><td class="text-right">{{ number_format($sale->paid_amount, 2) }}</td></tr>
        @if($sale->due_amount > 0)
        <tr><td style="color:red"><strong>Due</strong></td><td class="text-right" style="color:red"><strong>{{ number_format($sale->due_amount, 2) }}</strong></td></tr>
        @endif
    </table>
</div>

<div style="clear:both; margin-top:10px">
    <strong>Payment:</strong>
    @foreach($sale->payments as $p)
        {{ $p->method?->name }}: {{ number_format($p->amount, 2) }};
    @endforeach
</div>

<div class="footer">
    <p>{{ setting('receipt_footer', 'ধন্যবাদ আমাদের সাথে কেনাকাটা করার জন্য!') }}</p>
</div>

</body>
</html>