@extends('layouts.master')

@section('title', 'الاشعارات اليدوية ')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('provider.notifications') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-bell-o"></i>  الاشعارات اليدوية  </h1>
        </div>

             <div class="col-md-3 top_action top_button">
                <a class="btn btn-white btn-info btn-lg btn-bold" href="{{route('admin.notifications.add',$type)}}">
                    <i class="fa fa-plus"></i> أضافة اشعار جديد
                </a>
            </div>
     </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>  مسلسل</th>
                <th>عنوان الاشعار </th>
                <th> محتوي الاشعار </th>
                <th> تاريخ الانشاء </th>
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
                ajax: "{{ route('admin.notifications.data',$type) }}",
                columns: [
                    {name:'id'},
                    {name: 'title'},
                    {name: 'content'},
                    {name: 'created_at'},
                    {name:'action'}
                ],
            });
        });
    </script>
@stop
