@extends('layouts.master')

@section('title', ' القائمة البريدية ')

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('subscribtions') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="col-md-9 page-header">
                <h1><i class="menu-icon fa fa-hospital-o"></i>  القائمة البريدية  </h1>
            </div>
        </div>
        <div class="col-md-12">
            <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
                <thead>
                <tr>

                    <th> الاميل  </th>
                    <th> التاريخ   </th>
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
                ajax: "{{ route('admin.subscriptions.data') }}",
                columns: [
                    {name: 'email'},
                    {name: 'created_at'},
                    {name: 'action', orderable: false, searchable: false}
                ],
            });
        });
    </script>
@stop
