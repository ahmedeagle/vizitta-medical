@extends('layouts.master')

@section('title', 'الاشعارات اليدوية ')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('user.message') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-bell-o"></i>  الاشعارات اليدوية  </h1>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>  مسلسل</th>
                <th> ألاسم  </th>
                <th>  رقم الهاتف   </th>

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
                ajax: "{{ route('admin.receivers.data',[$notifyId,$type]) }}",
                columns: [
                    {name:'id'},
                    {name: '{{$type == 'providers' ? 'name_ar' : 'name'}}' },
                    {name: 'mobile'},

                ],
            });
        });
    </script>
@stop
