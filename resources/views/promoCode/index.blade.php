@extends('layouts.master')

@section('title', 'الرموز الترويجية')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('promoCode') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-gift"></i> الرموز الترويجية (Promo Codes) </h1>
        </div>
        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.promoCode.add') }}">
                <i class="fa fa-plus"></i> إضافة كود
            </a>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الرمز</th>

                <th> العنوان</th>
                <th>الوصف </th>
                <th>النوع</th>
                <th> نسبة الخصم لكوبون الخصم (%)</th>
                <th> نسبة التطبيق من كوبون الخصم (%)</th>
                <th> نسبة التطبيق من كوبون المدفوع (%)</th>
                <th>قيمه الكوبون</th>
                <th>العدد المتاح</th>
                <th>الحالة</th>
                <th>تاريخ الإنتهاء</th>
                <th>مقدم الخدمة</th>
                <th> التمييز   </th>
                <th>العمليات</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#dynamic-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.promoCode.data') }}",
                columns: [

                    {name: 'code'},
                    {name: 'title_ar'},
                    {name: 'features_ar'},
                    {name: 'coupons_type_id'},
                    {name: 'discount'},
                    {name: 'application_percentage'},
                    {name: 'paid_coupon_percentage'},
                    {name: 'price'},
                    {name: 'available_count'},
                    {name: 'status'},
                    {name: 'expired_at'},
                    {
                        name: 'provider.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'featured'},
                    {name: 'action', orderable: false, searchable: false}
                ],
            });
        });
    </script>
@stop
