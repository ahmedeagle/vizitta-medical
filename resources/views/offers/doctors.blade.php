@extends('layouts.master')

@section('title', 'الرموز الترويجية')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('promoCode-doctors') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-gift"></i>  </h1>
        </div>
       
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>رقم  الطبيب  </th>
                <th>اسم   الطبيب  </th>
                <th>الحاله</th>
               
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
                ajax: "{{ route('admin.promoCode.datadoctor',$promoCodeId) }}",
                columns: [ 
                    {name: 'doctor_id'},                    
                    {name: 'doctor.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'status'},                      
                ],
            });
        });
    </script>
@stop
