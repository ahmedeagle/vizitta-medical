@extends('layouts.master')

@section('title',  'التعليقات ')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('comments') !!}
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
                <th> التعليق</th>
                <th> صاحب التعليق</th>
                <th> مزود الخدمه</th>
                <th> الفرع</th>
                <th> تقييم مزود الخدمه</th>
                <th> الطبيب</th>
                <th> تقييم الطبيب</th>
                <th> وقت التقييم</th>
                <th>العمليات</th>
            </tr>
            </thead>
        </table>
    </div>
</div>

@if(isset($reservations) && $reservations -> count() > 0 )
    @foreach($reservations as $reservation)
        @include('includes.modals.rateModal',['reservation' => $reservation])
    @endforeach
@endif
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#dynamic-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.comments.data') }}",
                columns: [
                    {name: 'reservation_no'},
                    {name: 'rate_comment'},
                    {name: 'user.name'},
                    {name: 'mainprovider'},
                    {name: 'provider.name_ar'},
                    {name: 'provider_rate'},
                    {name: 'doctor.name_ar'},
                    {name: 'doctor_rate'},
                    {name: 'rate_date'},
                    {name: 'actioncomments'},
                ],
            });
        });

        $(document).on('click', '.edit_rate', function (e) {
            e.preventDefault();
            $('#edit_rate_Modal' + $(this).attr('data_reservation_id')).modal('toggle');
        });

        @if(Session::has('commentModalId'))
        $("#edit_rate_Modal{{Session::get('commentModalId')}}").modal('toggle');
        @endif
    </script>
@stop
