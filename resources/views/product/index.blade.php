@extends('layouts.app')
@section('css')
    <!-- Internal Data table css -->
    <link href="{{ URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css') }}" rel="stylesheet">
    <!--Internal   Notify -->
    <link href="{{ URL::asset('assets/plugins/notify/css/notifIt.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet">
@endsection

@section('page-header')
    <!-- breadcrumb -->
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h5 class="content-title mb-0 my-auto"> ادارة المنتجات</h5><span class="text-muted mt-1 tx-13 mr-2 mb-0">/
                    قائمة المنتجات</span>
            </div>
        </div>
    </div>
    <!-- breadcrumb -->
@endsection
@section('content')
    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    @if ($errors->any())
                        <div class="alert alert-outline-danger" role="alert">
                            <button aria-label="Close" class="close" data-dismiss="alert" type="button">
                                <span aria-hidden="true">&times;</span></button>
                            @foreach ($errors->all() as $error)
                                <strong>خطأ!</strong> {{ $error }}
                            @endforeach
                        </div>
                    @endif
                    @if (session()->has('success_created'))
                        <script>
                            window.onload = function() {
                                notif({
                                    msg: " تم حفط البيانات بنجاح",
                                    type: "success"
                                })
                            }
                        </script>
                    @endif

                    @if (session()->has('success_updated'))
                        <script>
                            window.onload = function() {
                                notif({
                                    msg: " تم تحديث البيانات بنجاح",
                                    type: "success"
                                })
                            }
                        </script>
                    @endif
                    @if (session()->has('success_deleted'))
                        <script>
                            window.onload = function() {
                                notif({
                                    msg: " تم حذف البيانات بنجاح",
                                    type: "success"
                                })
                            }
                        </script>
                    @endif

                    <div class="col-12 mg-t-20">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">

                            <!-- مجموعة الإضافة + الحذف -->
                            <div class="d-flex flex-column flex-md-row gap-1">

                                @can('اضافة منتج')
                                    <a class="btn btn-primary ml-2" href="{{ route('product.create') }}"><i class="fa fa-plus">
                                        </i>&nbsp;&nbsp;إضافة منتج
                                        </i></a>
                                @endcan
                                <a class="btn btn-success ml-2" href="{{ route('product.excel.create') }}">
                                    الاضافة بواسطة الاكسل</a>
                                <a href="#" class="modal-effect btn btn-info" data-target="#percentModal"
                                    data-toggle="modal"> تغيير
                                    الاسعار بالنسبة</a>
                                <div class="custom-control custom-checkbox align-self-center mr-2">
                                    <input type="checkbox" class="custom-control-input" id="no_price_filter">
                                    <label class="custom-control-label" for="no_price_filter">بدون سعر فقط</label>
                                </div>
                                <div class="custom-control custom-checkbox align-self-center mr-2">
                                    <input type="checkbox" class="custom-control-input" id="zero_stock_filter">
                                    <label class="custom-control-label" for="zero_stock_filter">صفر كمية فقط</label>
                                </div>
                            </div>

                            <!-- زر الرجوع -->
                            <a href="javascript:history.back()" class="btn btn-warning">
                                <i class="fa fa-arrow-right"></i>&nbsp;رجوع
                            </a>

                        </div>
                    </div>
                    {{-- <p class="tx-12 tx-gray-500 mb-2">Example of Valex Simple Table. <a href="">Learn more</a></p>  --}}
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1">
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-10p border-bottom-0">الاسم</th>
                                    <th class="wd-10p border-bottom-0">الاسم العلمي</th>
                                    <th class="wd-25p border-bottom-0">الكمية</th>
                                    <th class="wd-15p border-bottom-0">الشركة</th>
                                    <th class="wd-20p border-bottom-0">تاريخ الصلاحية</th>
                                    <th class="wd-5p">سعر البيع</th>
                                    <th class="wd-5p">سعر الشراء</th>
                                    <th class="wd-15p border-bottom-0">العمليات </th>

                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($products as $product)
                                    <tr data-id="{{ $product->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->name_en }}</td>
                                        <td data-zero-stock="{{ $product->stocks->sum('pivot.quantity') == 0 ? '1' : '0' }}">
                                            {{ $product->convertQuantity($product->stocks->sum('pivot.quantity')) ?? null }}
                                        </td>
                                        <td>{{ $product->category->name }}</td>
                                        <td>{{ $product->expire_date }}</td>
                                        <td data-zero-price="{{ (($product->units[0]->pivot->price ?? 0) == 0) ? '1' : '0' }}">
                                            {{ number_format($product->units[0]->pivot->price ?? 0) }}
                                            @if (($product->units[0]->pivot->price ?? 0) == 0)
                                                <span class="badge badge-warning">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ number_format($product->units[0]->pivot->price_purchase ?? 0) }}
                                            @if (($product->units[0]->pivot->price_purchase ?? 0) == 0)
                                                <span class="badge badge-warning">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            @can('تعديل منتج')
                                                <a class="btn btn-outline-success btn-sm"
                                                    href="{{ route('product.edit', $product->id) }}" title="تعديل">
                                                    <i class="fa fa-edit">
                                                    </i></a>
                                            @endcan
                                            @can('حذف منتج')
                                                <a class="modal-effect btn btn-outline-danger btn-sm" data-target="#deleteModal"
                                                    data-toggle="modal" href="#deleteModal" data-id="{{ $product->id }}"
                                                    title="حذف">
                                                    <i class="fa fa-trash">
                                                    </i></a>
                                            @endcan
                                            {{-- @can('الارصدة الافتتاحية للمنتجات')
                                                <a class="btn btn-outline-info btn-sm"
                                                     href="{{ route('product.opening',$product->id) }}" data-id="{{ $product->id }}"
                                                    title="الارصدة الافتتاحية للمنتجات">
                                                    <i class="fa fa-box">
                                                    </i></a>
                                            @endcan --}}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    <div class="modal" id="percentModal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content tx-size-sm">

                <div class="modal-body pd-y-20 pd-x-20">

                    <button aria-label="Close" class="close" data-dismiss="modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <div class="tx-center mg-b-20">
                        <h5 class="mg-b-5">تغيير أسعار المنتجات</h5>
                        <p class="tx-13 tx-gray-600 mg-b-0">
                            يمكنك تعديل أسعار المنتجات حسب القسم ونسبة التغيير
                        </p>
                    </div>

                    <form action="{{ route('product.percent') }}" method="post">
                        @csrf

                        {{-- القسم --}}
                        <div class="form-group">
                            <label for="category_id">
                                الأقسام
                            </label>

                            <select name="category_id[]" id="category_id" class="form-control" multiple>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                 يمكنك اختيار اكثر من قسم كما يمكنك عدم اختيار اي قسم  </small>
                        </div>

                        <div class="form-group">
                            <label for="percent">
                                نسبة التغيير
                            </label>

                            <div class="input-group">
                                <input type="number" name="percent" id="percent" class="form-control"
                                    placeholder="مثال: 10"  step="0.01" required>

                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <small class="text-muted">
                                أدخل نسبة الزيادة أو التغيير على أسعار المنتجات
                            </small>
                        </div>

                        <div class="tx-center mg-t-20">
                            <button class="btn ripple btn-primary pd-x-25" type="submit">
                                تغيير الأسعار
                            </button>

                            <button class="btn ripple btn-secondary pd-x-25" data-dismiss="modal" type="button">
                                إلغاء
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="deleteModal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content tx-size-sm">
                <div class="modal-body tx-center pd-y-20 pd-x-20">
                    <button aria-label="Close" class="close" data-dismiss="modal" type="button"><span
                            aria-hidden="true">&times;</span></button> <i
                        class="icon icon ion-ios-close-circle-outline tx-100 tx-danger lh-1 mg-t-20 d-inline-block"></i>
                    <form action="#" method="post" id="delete_product">
                        @csrf
                        @method('DELETE')
                        <h4 class="tx-danger mg-b-20">هل تريد حذف هذا السجل بالفعل ؟</h4>
                        <input type="hidden" id="name" readonly name="name" class="form-control">
                        <p class="mg-b-20 mg-x-20">في حالة الموافقة لا يمكنك التراجع عن هذا الاجراء !</p>
                        <button class="btn ripple btn-danger pd-x-25" type="submit">نعم , حذف السجل</button>
                        <button class="btn ripple btn-secondary" data-dismiss="modal" type="button">إلغاء</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('js')
    <!-- Internal Data tables -->
    <script src="{{ URL::asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/responsive.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/jquery.dataTables.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.bootstrap4.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/jszip.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/pdfmake.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/vfs_fonts.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/buttons.html5.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/buttons.print.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/buttons.colVis.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/responsive.bootstrap4.min.js') }}"></script>
    <!--Internal  Datatable js -->
    <script src="{{ URL::asset('assets/js/table-data.js') }}"></script>
    {{-- modals  --}}
    <script src="{{ URL::asset('assets/js/modal.js') }}"></script>
    <!--Internal  Notify js -->
    <script src="{{ URL::asset('assets/plugins/notify/js/notifIt.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/notify/js/notifit-custom.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/select2/js/select2.min.js') }}"></script>

    {{-- delete script  --}}
    <script>
        $(document).ready(function() {
            $('select').select2({
                placeholder:'الاقسام',
                width:'100%'
            });
            let productsTable = $('#example1').DataTable();

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (!$('#no_price_filter').is(':checked')) {
                    return true;
                }
                const cells = settings.aoData[dataIndex].nTr.getElementsByTagName('td');
                return cells[6] && cells[6].getAttribute('data-zero-price') === '1';
            });

            $('#no_price_filter').on('change', function() {
                productsTable.draw();
            });

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (!$('#zero_stock_filter').is(':checked')) {
                    return true;
                }
                const cells = settings.aoData[dataIndex].nTr.getElementsByTagName('td');
                return cells[3] && cells[3].getAttribute('data-zero-stock') === '1';
            });

            $('#zero_stock_filter').on('change', function() {
                productsTable.draw();
            });

            $('#deleteModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var modal = $(this);
                $('#delete_product').attr('action', '{{ route('product.destroy', ':id') }}'.replace(
                    ':id', id));
            });

        });
    </script>
@endsection
