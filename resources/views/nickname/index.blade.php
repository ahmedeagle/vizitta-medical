@extends('layouts.master')

@section('title', 'ألقاب الدكاتره')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('nickname') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-lightbulb-o"></i> ألقاب الدكاترة </h1>
        </div>
        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.nickname.add') }}">
                <i class="fa fa-plus"></i> إضافة لقب
            </a>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم بالعربيه</th>
                <th>الإسم بالإنجليزيه</th>
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
                ajax: "{{ route('admin.nickname.data') }}",
                columns: [
                    {name: 'name_ar'},
                    {name: 'name_en'},
                    {name: 'action', orderable: false, searchable: false}
                ],
            });
        });
    </script>
@stop
