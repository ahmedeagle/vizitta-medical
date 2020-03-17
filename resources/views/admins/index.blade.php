@extends('layouts.master')

@section('title', ' مستخدمي لوحة التحكم ')

@section('content')
@section('breadcrumbs')
{!! Breadcrumbs::render('admins') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-users"></i>   مستخدمي لوحة تحكم الادمن  </h1>
        </div>

        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.admins.add') }}">
                <i class="fa fa-plus"></i> إضافة مستخدم جديد
            </a>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم</th>
                <th>الجوال</th>
                 <th>البريد الإلكترونى</th>
                  <th>تاريخ الإنضمام</th>
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
            ajax: "{{ route('admin.admins.data') }}",
            columns: [
                {name: 'name_ar'},
                {name: 'mobile'},
                {name: 'email'},
                {name: 'created_at'},
                {name: 'action', orderable: false, searchable: false}
            ],
        });
    });
</script>
@stop
