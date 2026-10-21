<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: dejavusans, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 14px;
            margin: 0;
            padding: 0;
        }

        .invoice {
            width: 100%;
            margin: auto;
            background: #fff;
        }

        /* الهيدر */
        .invoice-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .invoice-header td {
            border: none;
            border-bottom: 7px solid #042d7a;
            padding: 6px;
            vertical-align: middle;
        }

        .invoice-header img {
            width: 60px;
            height: 60px;
        }

        .invoice-header h2 {
            margin: 0;
            font-size: 20pt;
            font-weight: bold;
            color: #042d7a;
        }

        .invoice-header p {
            margin: 3px 0;
        }

        /* العنوان */
        .modern-details {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 15px 0;
        }

        .modern-details td {
            background: #f9fbff;
            border: 1px solid #e3e8f3;
            padding: 10px 12px;
            text-align: center;
            vertical-align: middle;
        }

        .modern-details td.title-banner {
            background: #042d7a;
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            padding: 12px;
            border: none;
        }

        .modern-details .label {
            font-size: 13px;
            color: #6c757d;
        }

        .modern-details .value {
            font-size: 13pt;
            font-weight: bold;
            color: #042d7a;
        }

        /* الجدول */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: auto;
        }

        .invoice-table thead {
            display: table-header-group;
            background: #f1f1f1;
        }

        .invoice-table tfoot {
            display: table-row-group;
            background: #f9f9f9;
        }

        .invoice-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
            white-space: nowrap;
            font-size: 11pt;
        }

        .invoice-table thead th {
            font-weight: bold;
        }

        .invoice-table th.product-cell,
        .invoice-table td.product-cell {
            width: 38%;
            white-space: normal;
        }

        .invoice-table tfoot th,
        .invoice-table tfoot td {
            border: none;
            border-bottom: 1px solid #ddd;
        }

        .invoice-note {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .invoice-note td {
            border: 1px solid #999;
            padding: 8px 10px;
            font-size: 15px;
            white-space: pre-line;
        }
    </style>
</head>

<body>

    <div class="invoice">

        @php
            $logo = GeneralSiteSettings('logo');
            $logoPath = $logo ? public_path('image/logo/' . $logo) : null;
        @endphp

        <!-- الهيدر -->
        <table class="invoice-header">
            <tr>
                <td style="width:20%; text-align:right;">
                    @if ($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo">
                    @endif
                </td>

                <td style="width:60%; text-align:center;">
                    <h2>{{ GeneralSiteSettings('name') }}</h2>
                    <p>{{ GeneralSiteSettings('location') }}</p>
                    <p>{{ GeneralSiteSettings('phone') }}</p>
                </td>

                <td style="width:20%; text-align:left;">
                    @if ($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo">
                    @endif
                </td>
            </tr>
        </table>

        <!-- العنوان وبيانات الفاتورة -->
        <table class="modern-details">
            <tr>
                <td colspan="4" class="title-banner">فاتورة مبيعات</td>
            </tr>
            <tr>
                <td>
                    <div class="label">رقم الفاتورة</div>
                    <div class="value">{{ $sale_invoice->invoice_number }}</div>
                </td>
                <td>
                    <div class="label">تاريخ الفاتورة</div>
                    <div class="value">{{ $sale_invoice->date }}</div>
                </td>
                <td>
                    <div class="label">العميل</div>
                    <div class="value">{{ $sale_invoice->customer->tree4_name ?? '---' }}</div>
                </td>
                <td>
                    <div class="label">المندوب</div>
                    <div class="value">{{ $sale_invoice->representative->tree4_name ?? '---' }}</div>
                </td>
            </tr>
        </table>

        <!-- الجدول -->
        <table class="invoice-table">

            <thead>
                <tr>
                    <th>م</th>
                    <th class="product-cell">المنتج</th>
                    <th>الكمية</th>
                    <th>البونص</th>
                    <th>الوحدة</th>
                    <th>تاريخ الصلاحية</th>
                    <th>سعر الوحدة</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($sale_invoice->details as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="product-cell">{{ $item->product->name }}</td>
                        <td>{{ number_format($item->qty) }}</td>
                        <td>{{ number_format($item->bounce) }}</td>
                        <td>{{ $item->unit->name }}</td>
                        <td>{{ $item->expire_date }}</td>
                        <td>{{ number_format($item->price, 2) }}</td>
                        <td>{{ number_format($item->row_sub_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="4"></td>
                    <td colspan="2">جملة الفاتورة</td>
                    <td colspan="2">{{ number_format($sale_invoice->sub_total_amount + $sale_invoice->tax_amount, 2) }}</td>
                </tr>
                @if ((float) $sale_invoice->discount_amount > 0)
                    <tr>
                        <td colspan="4"></td>
                        <td colspan="2">الخصم</td>
                        <td colspan="2">{{ number_format($sale_invoice->discount_amount, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="4"></td>
                    <td colspan="2">صافي الفاتورة</td>
                    <td colspan="2">{{ number_format($sale_invoice->total_amount, 2) }}</td>
                </tr>
                @php
                    $cash = (float) $sale_invoice->cash_amount_total;
                    $bank = (float) $sale_invoice->bank_amount_total;
                    $showCash = $cash > 0 || $bank <= 0;
                    $showBank = $bank > 0 || $cash <= 0;
                @endphp
                @if ($showCash)
                    <tr>
                        <td colspan="4"></td>
                        <td colspan="2">مدفوع نقداً</td>
                        <td colspan="2">{{ number_format($sale_invoice->cash_amount_total, 2) }}</td>
                    </tr>
                @endif
                @if ($showBank)
                    <tr>
                        <td colspan="4"></td>
                        <td colspan="2">مدفوع بالبنك</td>
                        <td colspan="2">{{ number_format($sale_invoice->bank_amount_total, 2) }}</td>
                    </tr>
                @endif
                @if ($sale_invoice->transaction_no)
                    <tr>
                        <td colspan="4"></td>
                        <td colspan="2">رقم عملية البنك</td>
                        <td colspan="2">{{ $sale_invoice->transaction_no }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="4"></td>
                    <td colspan="2">آجل</td>
                    <td colspan="2">{{ number_format($sale_invoice->deferend_amount, 2) }}</td>
                </tr>
                @if ($sale_invoice->customer_id != null)
                    <tr>
                        <td colspan="4"></td>
                        <td colspan="2">جملة المطالبة السابقة</td>
                        <td colspan="2">{{ number_format($madinTotal - $dainTotal) }}</td>
                    </tr>
                    @if ($sale_invoice->deferend_amount > 0)
                        <tr>
                            <td colspan="4"></td>
                            <td colspan="2">جملة المطالبة الكلية</td>
                            <td colspan="2">{{ number_format($allMadinTotal - $allDainTotal) }}</td>
                        </tr>
                    @endif
                @endif
            </tfoot>

        </table>

        @if ($sale_invoice->note)
            <table class="invoice-note">
                <tbody>
                    <tr>
                        <td><strong>ملاحظات:</strong> {{ $sale_invoice->note }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

    </div>

</body>

</html>
