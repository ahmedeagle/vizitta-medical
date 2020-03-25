@extends('layouts.master')

@section('title', 'الافرع الخاصه بالعرض ')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('offers-branches') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-gift"></i></h1>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>رقم الفرع</th>
                <th>اسم الفرع</th>
                <th>الحاله</th>

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
                ajax: "{{ route('admin.offers.databranch', $offerId) }}",
                columns: [
                    {name: 'branch_id'},
                    {
                        name: 'branch.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'status'},
                ],
            });
        });
    </script>
@stop
