@extends('layouts.master')

@section('title',  ' البلاغات  ')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('reports') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-bell-o"></i> التعليقات </h1>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th> رقم الحجز</th>
                <th> نوع البلاغ</th>
                <th> صاحب البلاغ</th>
                <th> مزود الخدمه</th>
                <th> وقت البلاغ</th>
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
                ajax: "{{ route('admin.reports.data') }}",
                columns: [
                    {name: 'reservation_no'},
                    {name: 'reportingType.name_ar'},
                    {name: 'user.name'},
                    {name: 'provider.name_ar'},
                    {name: 'created_at'},
                    {name: 'action'}
                ],
            });
        });
    </script>
@stop
