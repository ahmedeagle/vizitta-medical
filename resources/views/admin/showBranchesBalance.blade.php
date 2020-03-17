@extends('layouts.master')

@section('title', 'رصيد الافرع ')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('branches.balance') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> رصيد الافرع الخاص بمقدم الخدمه - {{$provider -> name_ar}}</h1>
        </div>
    </div>

    <div class="space-12"></div>
    <div class="col-md-12">
        <h4 class="widget-title smaller"><i class="ace-icon fa fa-credit-card"></i> تفاصيل الرصيد </h4>
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم</th>
                <th>الرصيد </th>
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
                ajax: "{{ route('admin.data.branches',$provider -> id) }}",
                columns: [
                    {name: 'name_ar'},
                    {name:  'balance' },
                    {name: 'admin_branches_action', orderable: false, searchable: false}
                ],
            });
        });
    </script>
@stop
