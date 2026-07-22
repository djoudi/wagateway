<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    /* dompdf has limited CSS support: no flexbox, no grid, no custom properties.
       Kept intentionally simple — table-based layout for reliable rendering. */
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #16211C; direction: rtl; }
    .header { width: 100%; border-bottom: 3px solid #2FA66B; padding-bottom: 14px; margin-bottom: 24px; }
    .brand { font-size: 22px; font-weight: bold; color: #1B7A4D; }
    .doc-title { font-size: 16px; color: #5B6660; margin-top: 4px; }
    table.meta { width: 100%; margin-bottom: 20px; }
    table.meta td { padding: 3px 0; font-size: 11px; }
    table.meta .label { color: #5B6660; width: 120px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.items th { background: #E7F3EC; color: #1B7A4D; text-align: right; padding: 8px 10px; border: 1px solid #CBE9D7; font-size: 11px; }
    table.items td { padding: 8px 10px; border: 1px solid #DDE3DC; font-size: 12px; }
    table.total { width: 100%; margin-top: 10px; }
    table.total td { padding: 6px 10px; font-size: 12px; }
    table.total .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #2FA66B; color: #1B7A4D; }
    .status-paid { display: inline-block; background: #E7F3EC; color: #1B7A4D; padding: 3px 10px; font-size: 10px; font-weight: bold; }
    .footer { margin-top: 40px; padding-top: 14px; border-top: 1px solid #DDE3DC; font-size: 9.5px; color: #9AACA3; text-align: center; }
</style>
</head>
<body>

    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ config('app.name', 'WaGateway') }}</div>
                <div class="doc-title">فاتورة رسمية</div>
            </td>
            <td style="text-align:left;">
                <span class="status-paid">مدفوعة</span>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="label">رقم الفاتورة</td>
            <td>{{ $invoice->invoice_number }}</td>
            <td class="label">تاريخ الإصدار</td>
            <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td class="label">تاريخ الدفع</td>
            <td>{{ $invoice->paid_at?->format('Y-m-d H:i') }}</td>
            <td class="label">طريقة الدفع</td>
            <td>{{ match($invoice->payment_method) { 'card' => 'بطاقة (Edahabia/CIB)', 'ccp' => 'CCP', 'bank_transfer' => 'تحويل بنكي', default => $invoice->payment_method } }}</td>
        </tr>
        <tr>
            <td class="label">الفاتورة إلى</td>
            <td colspan="3">{{ $invoice->user->name }} — {{ $invoice->user->email }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>الوصف</th>
                <th>دورة الفوترة</th>
                <th>المبلغ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>اشتراك خطة {{ $invoice->plan->name }}</td>
                <td>{{ $invoice->billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}</td>
                <td>{{ number_format($invoice->amount) }} {{ $invoice->currency }}</td>
            </tr>
        </tbody>
    </table>

    <table class="total">
        <tr>
            <td style="text-align:left; width:80%;"></td>
            <td class="total-row">الإجمالي: {{ number_format($invoice->amount) }} {{ $invoice->currency }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ config('app.name', 'WaGateway') }} — هذه الفاتورة صادرة إلكترونياً ولا تتطلب توقيعاً أو ختماً.<br>
        للاستفسارات: billing@wagateway.dz
    </div>

</body>
</html>
