@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/print-document.css') }}?v={{ filemtime(public_path('assets/css/print-document.css')) }}">
    <style>
        .card {
            border: none !important;
        }

        .invoice {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
        }

        .invoice-header {
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 7px solid #042d7a;
        }

        .invoice-header img {
            width: 100px;
        }

        .invoice-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .modern-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 15px;
            direction: rtl;
        }

        /* العنوان */
        .modern-details .detail-box:first-child {
            grid-column: 1 / -1;
            background: #042d7a;
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            padding: 12px;
        }

        /* البوكسات العادية */
        .detail-box {
            background: #f9fbff;
            border: 1px solid #e3e8f3;
            border-radius: 10px;
            padding: 10px 12px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .detail-box .label {
            display: block;
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 3px;
        }

        .detail-box .value {
            font-size: 16px;
            font-weight: bold;
            color: #042d7a;
        }

        /* نخلي العنوان أبيض */
        .modern-details .detail-box:first-child .value {
            color: #fff;
        }


        .table-container {
            position: relative;
        }

        .table-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('{{ asset('image/logo/17307014763895.png') }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.06;
            z-index: 1;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            background-color: transparent;
            position: relative;
            z-index: 2;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        .invoice-table tfoot {
            background: #f9f9f9;
        }

        .invoice-table tfoot th,
        .invoice-table tfoot td {
            border: none;
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }


        @media print {
            #print_Button , .create_invoice{
                display: none !important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="row row-sm">
        <div class="col-md-12 col-xl-12">
            <div class=" main-content-body-invoice" id="print">
                <div class="card card-invoice">
                    <div class="card-body">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="invoice">
                                        <div class="invoice-header">
                                            @if (GeneralSiteSettings('logo'))
                                                <img src="{{ asset('image/logo/' . GeneralSiteSettings('logo')) }}"
                                                    alt="Logo">
                                            @endif
                                            <div class="text-center">
                                                <h1>{{ GeneralSiteSettings('name') }}</h1>
                                                <h6>{{ GeneralSiteSettings('location') }}</h6>
                                                <h6>{{ GeneralSiteSettings('phone') }}</h6>
                                            </div>
                                            @if (GeneralSiteSettings('logo'))
                                                <img src="{{ asset('image/logo/' . GeneralSiteSettings('logo')) }}"
                                                    alt="Logo">
                                            @endif
                                        </div>

                                        <div class="invoice-details modern-details">
                                            <div class="detail-box">
                                                <span class="value">فاتورة مردود مبيعات</span>
                                            </div>
                                            <div class="detail-box">
                                                <span class="label">رقم الفاتورة</span>
                                                <span class="value">{{ $sale_invoice_return->invoice_number }}</span>
                                            </div>

                                            <div class="detail-box">
                                                <span class="label">تاريخ الفاتورة</span>
                                                <span class="value">{{ $sale_invoice_return->date }}</span>
                                            </div>

                                            <div class="detail-box">
                                                <span class="label">العميل</span>
                                                <span
                                                    class="value">{{ $sale_invoice_return->customer->tree4_name ?? '---' }}</span>
                                            </div>

                                            <div class="detail-box">
                                                <span class="label">المندوب</span>
                                                <span
                                                    class="value">{{ $sale_invoice_return->representative->tree4_name ?? '---' }}</span>
                                            </div>
                                        </div>


                                        <div class="table-container">
                                            <table class="invoice-table">
                                                <thead>
                                                    <tr>
                                                        <th>م</th>
                                                        <th class="product-cell">المنتج</th>
                                                        <th>الكمية</th>
                                                        <th>البونص</th>
                                                        <th>تاريخ الصلاحية</th>
                                                        <th>سعر الوحدة</th>
                                                        <th>الإجمالي</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sale_invoice_return->details as $item)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td class="product-cell">{{ $item->product->name }}</td>
                                                            <td>{{ number_format($item->qty) }}</td>
                                                            <td>{{ number_format($item->bounce) }}</td>
                                                            <td>{{ $item->expire_date }}</td>
                                                            <td>{{ number_format($item->price, 2) }}</td>
                                                            <td>{{ number_format($item->row_sub_total, 2) }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="3"></td>
                                                        <td colspan="2">جملة الفاتورة</td>
                                                        <td colspan="2">
                                                            {{ number_format($sale_invoice_return->sub_total_amount + $sale_invoice_return->tax_amount, 2) }}
                                                        </td>
                                                    </tr>
                                                    @if ((float) $sale_invoice_return->discount_amount > 0)
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td colspan="2">الخصم</td>
                                                            <td colspan="2">
                                                                {{ number_format($sale_invoice_return->discount_amount, 2) }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td colspan="3"></td>
                                                        <td colspan="2">صافي الفاتورة</td>
                                                        <td colspan="2">
                                                            {{ number_format($sale_invoice_return->total_amount, 2) }}</td>
                                                    </tr>
                                                    @php
                                                        $cash = (float) $sale_invoice_return->cash_amount_total;
                                                        $bank = (float) $sale_invoice_return->bank_amount_total;
                                                        $showCash = $cash > 0 || $bank <= 0;
                                                        $showBank = $bank > 0 || $cash <= 0;
                                                    @endphp
                                                    @if ($showCash)
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td colspan="2">مدفوع نقداً</td>
                                                            <td colspan="2">
                                                                {{ number_format($sale_invoice_return->cash_amount_total, 2) }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    @if ($showBank)
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td colspan="2">مدفوع بالبنك</td>
                                                            <td colspan="2">
                                                                {{ number_format($sale_invoice_return->bank_amount_total, 2) }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    @if ($sale_invoice_return->transaction_no)
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td colspan="2">رقم عملية البنك</td>
                                                            <td colspan="2">{{ $sale_invoice_return->transaction_no }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td colspan="3"></td>
                                                        <td colspan="2">آجل</td>
                                                        <td colspan="2">
                                                            {{ number_format($sale_invoice_return->deferend_amount, 2) }}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        @if ($sale_invoice_return->note)
                                            <div class="invoice-note"><strong>ملاحظات:</strong> {{ $sale_invoice_return->note }}</div>
                                        @endif

                                    </div>
                                </div><!-- bd -->
                            </div><!-- bd -->
                        </div>
                        <hr class="mg-b-40 no-print">
                        <button class="btn btn-primary float-left mt-3 mr-2" id="print_Button" onclick="printDiv()">
                            <i class="mdi mdi-printer ml-1"></i> طبـاعة
                        </button>
                        <a href="{{ route('sale_invoice_return.create') }}" class="btn btn-outline-primary float-left mt-3 mr-2 create_invoice">
                            <i class="mdi mdi-plus ml-1"></i> فاتورة مردود مبيعات جديدة 
                        </a>
                    </div>
                </div>
            </div>
        </div><!-- COL-END -->
    </div>
@endsection

@section('js')
    <script>
        function printDiv() {
            window.print();
        }
        window.addEventListener('afterprint', function () {
            window.location.href = '{{ route('sale_invoice_return.index') }}';
        });
    </script>
@endsection
