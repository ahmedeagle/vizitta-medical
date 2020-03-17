@extends('layouts.master')

@section('title', ' تعديل موعد الحجز  ')

@section('styles')


    <!-- include the calendar js and css files -->

    {!! Html::style('css/zabuto_calendar.min.css') !!}

    <style>
        .dangerc {
            background-color: #fa5c66;
        }

        .list_se_f li {
            display: inline-block;
        }

        .list_se_f li a {
            display: block;
            background-color: #fff;
            padding: 7px;
            border: 1px solid #eaeaea;
            margin-left: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 14px;
        }
    </style>

@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('view.reservation') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-image"></i> الحجز رقم {{ $reservation->reservation_no }}</h1>
        </div>
    </div>
    <br><br>
    <div class="col-md-12" id="availableCont">
        أختر يوم لعرض المواعيد المتاحه للحجز
    </div>

    <br><br>
    <div class="col-md-6 col-md-offset-3" id="messages">
    </div>
    <br><br> <br><br>

    <input type="hidden" id="reservation_id" value="{{$reservation -> reservation_no}}">

    <div class="col-md-12" style="direction: rtl">
        <!-- define the calendar element -->

        <div id="date-popover" class="popover top"
             style="cursor: pointer; display: block; margin-left: 33%; margin-top: -50px; width: 175px;">
            <div class="arrow"></div>
            <h3 class="popover-title" style="display: none;"></h3>

            <div id="date-popover-content" class="popover-content"></div>
        </div>

        <div id="my-calendar"></div>
        <div class="space-12"></div>
    </div>
</div>


@stop


@section('scripts')
    {!! Html::script('js/zabuto_calendar.min.js') !!}

    <script type="application/javascript">

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function () {
            $("#date-popover").popover({html: true, trigger: "manual"});
            $("#date-popover").hide();
            $("#date-popover").click(function (e) {
                $(this).hide();
            });
            $("#my-calendar").zabuto_calendar({
                show_previous: false,
                language: "ar",
                today: true,
                action: function () {
                    return myDateFunction(this.id, false);
                },
                action_nav: function () {
                    return myNavFunction(this.id);
                },
                ajax: {
                    url: "{{route('doctorDays')}}",
                    modal: true
                },
                legend: [
                    {type: "block", classname: 'dangerc', label: "ايام غير متاحه لدي الطبيب "}
                ],
                weekstartson: 0,
                nav_icon: {
                    next: '<i class="fa fa-chevron-circle-left fa-2x"></i>',
                    prev: '<i class="fa fa-chevron-circle-right fa-2x"></i>'
                }

            });
        });

        function myDateFunction(id, fromModal) {
            $("#date-popover").hide();
            if (fromModal) {
                $("#" + id + "_modal").modal("hide");
            }
            var date = $("#" + id).data("date");
            var hasEvent = $("#" + id).data("hasEvent");
            if (hasEvent && !fromModal) {
                return false;
            }
            $('#messages').empty();
            $.ajax({
                type: 'get',
                url: "{{url('mc33/doctor/availableTimes')}}/" + date,

                success: function (data) {

                    $('#availableCont').empty().append(data.content);
                }
            });
            return true;
        }

        function myNavFunction(id) {
            $("#date-popover").hide();
            var nav = $("#" + id).data("navigation");
            var to = $("#" + id).data("to");
        }


        $(document).on('click', '.resTime', function (e) {
            e.preventDefault();
            $('#messages').empty();
            $('#availableCont').empty('أختر يوم لعرض المواعيد المتاحه للحجز');
            $.ajax({
                type: 'post',
                url: "{{route('admin.reservation.datetime.update')}}",
                data: {
                    'reservation_no': $('#reservation_id').val(),
                    'day_date': $(this).attr('data_date'),
                    'from_time': $(this).attr('data_from'),
                    'to_time': $(this).attr('data_to')
                },
                success: function (data) {
                    if (data.errNum == 1) {
                        $('#messages').empty().append('<p class="text-danger" ">'+data.message+'</p>');
                    }
                    if (data.errNum == 0) {
                        $('#messages').empty().append('<p class="text-success" ">'+data.message+'</p>');
                    }
                }
            });
        });
    </script>

@stop
