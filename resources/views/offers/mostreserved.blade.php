@extends('layouts.master')

@section('title', 'اكثر العروض حجزا')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('mostreserved') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-users"></i> اكثر العروض حجزا </h1>
        </div>
    </div>
    <div class="col-md-12">
        <table id="mostVisited-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>اسم العرض</th>
                <th>عدد حجوزات العرض</th>
                <th>أسم مقدم الخدمه</th>
            </tr>
            </thead>
            <tbody>
            @if(isset($reservations) &&  $reservations -> count() > 0)
                @foreach($reservations as $reservation)
                    <tr>
                        <td>{{$reservation -> coupon-> title}}</td>
                        <td>{{$reservation -> count}}</td>
                        <td>{{$reservation -> coupon-> provider -> name_ar}}</td>
                    </tr>

                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>
@stop

@section('scripts')
    <script>

        $(document).ready(function () {
            $('#mostVisited-table').DataTable(
                {
                    "ordering": false
                }
            );
        });
    </script>


@stop
