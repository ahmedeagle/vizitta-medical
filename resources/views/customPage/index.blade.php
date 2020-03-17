@extends('layouts.master')

@section('title', 'الصفحات الفرعية')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('customPage') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-file"></i> الصفحات الفرعية </h1>
        </div>
        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.customPage.add') }}">
                <i class="fa fa-plus"></i> إضافة صفحة فرعية
            </a>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>العنوان بالعربيه</th>
                <th>العنوان بالإنجليزيه</th>
                <th>الحالة</th>
                <th>خاص بالمستخدم</th>
                <th>خاص بمقدم الخدمة</th>
                <th>العمليات</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#dynamic-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.customPage.data') }}",
                columns: [
                    {name: 'title_ar'},
                    {name: 'title_en'},
                    {name: 'status'},
                    {name: 'user'},
                    {name: 'provider'},
                    {name: 'action', orderable: false, searchable: false}
                ],
            });
        });
    </script>
@stop
