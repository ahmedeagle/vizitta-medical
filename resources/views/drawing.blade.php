<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        #loading {
            background: #f4f4f2;
            height: 100%;
            left: 0;
            margin: auto;
            position: fixed;
            z-index: 1000;
            top: 0;
            width: 100%;
        }

        .bokeh {
            border: 0.01em solid rgba(150, 150, 150, 0.1);
            border-radius: 50%;
            font-size: 100px;
            height: 1em;
            list-style: outside none none;
            margin: 0 auto;
            position: relative;
            top: 35%;
            width: 1em;
            z-index: 2147483647;
        }

        .bokeh li {
            border-radius: 50%;
            height: 0.2em;
            position: absolute;
            width: 0.2em;
        }

        .bokeh li:nth-child(1) {
            animation: 1.13s linear 0s normal none infinite running rota, 3.67s ease-in-out 0s alternate none infinite running opa;
            background: #00c176 none repeat scroll 0 0;
            left: 50%;
            margin: 0 0 0 -0.1em;
            top: 0;
            transform-origin: 50% 250% 0;
        }

        .bokeh li:nth-child(2) {
            animation: 1.86s linear 0s normal none infinite running rota, 4.29s ease-in-out 0s alternate none infinite running opa;
            background: #ff003c none repeat scroll 0 0;
            margin: -0.1em 0 0;
            right: 0;
            top: 50%;
            transform-origin: -150% 50% 0;
        }

        .bokeh li:nth-child(3) {
            animation: 1.45s linear 0s normal none infinite running rota, 5.12s ease-in-out 0s alternate none infinite running opa;
            background: #fabe28 none repeat scroll 0 0;
            bottom: 0;
            left: 50%;
            margin: 0 0 0 -0.1em;
            transform-origin: 50% -150% 0;
        }

        .bokeh li:nth-child(4) {
            animation: 1.72s linear 0s normal none infinite running rota, 5.25s ease-in-out 0s alternate none infinite running opa;
            background: #88c100 none repeat scroll 0 0;
            margin: -0.1em 0 0;
            top: 50%;
            transform-origin: 250% 50% 0;
        }

        @keyframes opa {
            12% {
                opacity: 0.8;
            }
            19.5% {
                opacity: 0.88;
            }
            37.2% {
                opacity: 0.64;
            }
            40.5% {
                opacity: 0.52;
            }
            52.7% {
                opacity: 0.69;
            }
            60.2% {
                opacity: 0.6;
            }
            66.6% {
                opacity: 0.52;
            }
            70% {
                opacity: 0.63;
            }
            79.9% {
                opacity: 0.6;
            }
            84.2% {
                opacity: 0.75;
            }
            91% {
                opacity: 0.87;
            }
        }

        @keyframes rota {
            100% {
                transform: rotate(360deg);
            }
        }

        .loader {
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #d15b47;
            border-bottom: 16px solid #d15b47;
            width: 90px;
            height: 90px;
            -webkit-animation: spin 2s linear infinite;
            animation: spin 2s linear infinite;
        }

        @-webkit-keyframes spin {
            0% {
                -webkit-transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }


    </style>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="{!! asset('favicon.ico') !!}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> السحوبات </title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet"/>
    {!! Html::style('css/form.css') !!}
    {!! Html::style('css/bootstrap.min.css') !!}
    {!! Html::style('css/bootstrap-grid.min.css') !!}
    {!! Html::style('css/font-awesome.min.css') !!}
    {!! Html::style('css/fonts.googleapis.com.css') !!}
    {!! Html::style('css/ace-rtl.min.css') !!}
    {!! Html::style('css/ace.min.css') !!}
    {!! Html::style('css/ace-skins.min.css') !!}
    {!! Html::style('css/main.css') !!}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
</head>
<body class="no-skin rtl">
<div id="loading">
    <ul class="bokeh">
        <li></li>
        <li></li>
        <li></li>
    </ul>
</div>

<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state w-100" id="navbar-container">
        <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
            <span class="sr-only">Toggle sidebar</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="{{ asset("images/male.png") }}" alt="Admin" />
                        <span class="user-info">
							<small>مرحبا,</small>
							@if(Auth::user()){{ Auth::user()->name_ar }}@endif
                        </span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-blue dropdown-caret dropdown-close">
                        <li>
                            <a href="{{ route('admin.data.information.edit') }}">
                                <i class="ace-icon fa fa-user"></i>
                                تعديل الملف الشخصى
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('Logout') }}">
                                <i class="ace-icon fa fa-power-off"></i>
                                تسجيل الخروج
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>


<div class="main-container ace-save-state" id="main-container">
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">

            </div>
            <div class="page-content">
                <div class="col-md-12">
                    <div class="page-header">
                        <h1><i class="menu-icon fa fa-gift"></i> السحب والهدايا </h1>
                    </div>
                </div>

                <div class="col-md-12">
                    <form class="form" action="" method="">
                        <div class="form-group has-float-label col-sm-12">
                            <input type="number" name="amount" id="amountOfGifts" min="1" max="" class="form-control">
                            <label for="reservation_period"> عدد السحب <span class="astric">*</span></label>
                            <small class="text-danger" id="amount_error"></small>
                        </div>

                        <div class="col-sm-12">

                            <span class="text-center" style="font-size: 16px;">أختر عياده لعرض الهدايا:- </span>
                            <br><br><br>
                            <div class="row">
                                @if(isset($providers) && $providers -> count() > 0)
                                    @foreach($providers as $index =>  $provider)
                                        <div class="col-md-4">
                                            <label class="radio-inline" style="user-select: none;">
                                                <input style="margin: -8px -19px 0 0" data-id="{{$provider -> id}}"
                                                       class="providerdraw"
                                                       type="radio" name="providers[]" style=" display: inline-block"
                                                       value="{{$provider -> id}}"> {{$provider -> name_ar}}
                                            </label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <small class="text-danger" id="provider_id_error"></small>
                        </div>

                        <div class="col-sm-12" style="padding-top: 50px; display: none;" id="giftsContainer">
                            <span class="text-center" style="font-size: 16px;">أختر هدية للسحب عليها:- </span>
                            <br><br><br>
                            <div class="row" id="appendGift">
                            </div>
                        </div>

                        <div class="form-group col-sm-12 submit text-center center-block " style="display: none;" id="loader">
                            <div class="loader center-block"></div>
                        </div>

                        <br> <br>
                        <div class="form-group col-sm-12 submit text-center" style="padding-top: 60px;">
                            <button class="btn btn-danger btn-lg" id="submitBtn" type="button" disabled> أجراء عملية
                                السحب
                            </button>
                        </div>

                        <form>
                </div>

                <br><br>
                <div class="col-md-12" id="usersContainer" style="display: none;">
                    <table id="giftstable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>رقم الهاتف</th>
                        </tr>
                        </thead>
                        <tbody id="appendUsers">
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <br>
    <br>
    <br>
    <br>
{!! Html::script('bower_components/jquery/js/jquery.min.js') !!}
{!! Html::script('bower_components/jquery-ui/js/jquery-ui.min.js') !!}
{!! Html::script('bower_components/popper.js/js/popper.min.js') !!}
{!! Html::script('bower_components/bootstrap/js/bootstrap.min.js') !!}
{!! Html::script('js/ace-extra.min.js') !!}

<!-- page specific plugin scripts -->
    <script src="https://cdn.ckeditor.com/4.6.2/standard/ckeditor.js"></script>
    <!-- ace scripts -->
    {!! Html::script('js/ace-elements.min.js') !!}
    {!! Html::script('js/ace.min.js') !!}
    {!! Html::script('js/script.js') !!}
    {!! Html::script('js/jquery.dataTables.min.js') !!}
    {!! Html::script('js/jquery.dataTables.bootstrap.min.js') !!}
    <script src="https://cdn.datatables.net/buttons/1.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.4.1/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    {!! Html::script('js/vfs_fonts.js') !!}
    <script src="https://cdn.datatables.net/buttons/1.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
    <script>


        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function () {

            $('#loading').hide();
            $.extend(true, $.fn.dataTable.defaults, {
                "language": {
                    "lengthMenu": "عرض _MENU_ سجلات للصفحة",
                    "zeroRecords": "لا يوجد بيانات",
                    "info": "عرض صفحة _PAGE_ من _PAGES_",
                    "infoEmpty": "لا يوجد سجلات متاحة",
                    "infoFiltered": "(تصفية من _MAX_ سجل)"
                },
            });
        });

        $("input[required], select[required]").attr("oninvalid", "this.setCustomValidity('يرجى ملىء هذا الحقل')");
        $('.hover_popup').click(function () {
            $('.hover_popup').hide();
        });
        $('.popupCloseButton').click(function () {
            $('.hover_popup').hide();
        });


        localStorage.removeItem('working_hours');
        document.cookie = "working_hours" + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
        localStorage.removeItem('working_hours');
        document.cookie = "working_hoursedit" + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
        localStorage.removeItem('working_hoursedit');


            @if(Request::is('mc33/doctor/edit/*'))
            @if(isset($times)&&$times -> count()>0)
        var working_hoursedit = [];
            @foreach($times as $time)
        var working_hour = {
                day: "{{$time ->day_code}}",
                from: "{{$time ->from_time}}",
                to: "{{$time ->to_time}}",
            };
        working_hoursedit.push(working_hour);
        @endforeach
        @endif
        localStorage.setItem('working_hoursedit', JSON.stringify(working_hoursedit));
        console.log(JSON.parse(localStorage.getItem('working_hoursedit')));
        @endif

        $(document).on('click', '.addShiftTime', function (e) {
                e.preventDefault();

                let counter = $(this).attr('data_counter');
                let day_ar = $(this).attr('data_day_ar');
                let day_en = $(this).attr('data_day_en');


                let day = $('.day' + day_en + counter).val();
                let from = $('.from' + day_en + counter).val();
                let to = $('.to' + day_en + counter).val();


                if (day == "" || day == null || from == "" || from == null || to == "" || to == null) {
                    alert('لابد من ادخال توقيت من والي ثم الضغط علي ايقونه زائد للاضافه ');
                    return false;
                }

                $('.from' + day_en + counter).prop('disabled', true);
                $('.to' + day_en + counter).prop('disabled', true);

                $(this).remove();

                var working_hour = {
                    day: day,
                    from: from,
                    to: to,
                };

                @if(Request::is('mc33/doctor/create'))
                if (localStorage.getItem('working_hours') === null || localStorage.getItem('working_hours') === [] || localStorage.getItem('working_hours') === undefined) {
                    var working_hours = [];
                    working_hours.push(working_hour);
                    localStorage.setItem('working_hours', JSON.stringify(working_hours));
                } else {
                    var working_hours = JSON.parse(localStorage.getItem('working_hours'));
                    //add bookmark into array
                    working_hours.push(working_hour);
                    localStorage.setItem('working_hours', JSON.stringify(working_hours));
                }
                @endif

                    @if(Request::is('mc33/doctor/edit/*'))
                if (localStorage.getItem('working_hoursedit') === null || localStorage.getItem('working_hoursedit') === [] || localStorage.getItem('working_hoursedit') === undefined) {
                    var working_hours = [];
                    working_hours.push(working_hour);
                    localStorage.setItem('working_hoursedit', JSON.stringify(working_hours));
                } else {
                    var working_hours = JSON.parse(localStorage.getItem('working_hoursedit'));
                    //add bookmark into array
                    working_hours.push(working_hour);
                    localStorage.setItem('working_hoursedit', JSON.stringify(working_hours));
                }
                @endif


                $.ajax({
                    url: "{{route('doctor.addshifttimes')}}",

                    data: {
                        'counter': counter,
                        'day_ar': day_ar,
                        'day_en': day_en
                    },
                    type: 'post',
                    success: function (data) {
                        $('.order' + day_en + counter).after(data.content);
                        $('.add_minus' + day_en + counter).empty().append("<i    data_counter='" + counter + "'   data_from='" + from + "'     data_to='" + to + "'   data_day_en='" + day_en + "' data_day_ar='" + day_ar + "'   class='fa fa-minus fa-2x removeShiftTime'></i>");
                    },
                    error: function (reject) {
                    }
                });

                console.log(JSON.parse(localStorage.getItem('working_hours')));
                console.log(JSON.parse(localStorage.getItem('working_hoursedit')));
            }
        );

        $(document).on('click', '.removeShiftTime', function (e) {
            e.preventDefault();

            $(this).closest('.timerow').remove();
            let day = $(this).attr('data_day_en');
            let from = $(this).attr('data_from');
            let to = $(this).attr('data_to');

            @if(Request::is('mc33/doctor/create'))
            if (localStorage.getItem('working_hours') === null || localStorage.getItem('working_hours') === [] || localStorage.getItem('working_hours') === undefined) {

            } else {
                var working_hours = JSON.parse(localStorage.getItem('working_hours'));

                working_hours.forEach(function (time, index) {
                    if (time.day == day && time.from == from && time.to == to) {
                        working_hours.splice(index, 1); // first element removed
                        localStorage.setItem('working_hours', JSON.stringify(working_hours));
                        console.log(JSON.parse(localStorage.getItem('working_hours')));
                    }
                });

            }
            @endif

                @if(Request::is('mc33/doctor/edit/*'))
            if (localStorage.getItem('working_hoursedit') === null || localStorage.getItem('working_hoursedit') === [] || localStorage.getItem('working_hoursedit') === undefined) {

            } else {
                var working_hours = JSON.parse(localStorage.getItem('working_hoursedit'));

                working_hours.forEach(function (time, index) {
                    if (time.day == day && time.from == from && time.to == to) {
                        working_hours.splice(index, 1); // first element removed
                        localStorage.setItem('working_hoursedit', JSON.stringify(working_hours));
                        console.log(JSON.parse(localStorage.getItem('working_hoursedit')));
                    }
                });

            }
            @endif

        });
        getCookie = function (name) {
            var r = document.cookie.match("\\b" + name + "=([^;]*)\\b");
            return r ? r[1] : null;
        };

        $(document).on('click', '#postWorkingHourse', function (e) {
            e.preventDefault();
            @if(Request::is('mc33/doctor/create'))
                document.cookie = "working_hours=" + localStorage.getItem('working_hours') + ";path=/";
            console.log(getCookie('working_hours'));

            @endif
            @if(Request::is('mc33/doctor/edit/*'))
            //create new one
            document.cookie = "working_hoursedit=" + localStorage.getItem('working_hoursedit') + ";path=/";
            console.log('cookie work here');
            console.log(getCookie('working_hoursedit'));
            @endif
            $('.doctorForm').submit();
        });
        $(document).on('click', '.removeEditTime', function (e) {
            e.preventDefault();
            $(this).closest('.timerow').remove();
            e.preventDefault();
            $(this).closest('.timerow').remove();
            let day = $(this).attr('data_day_en');
            let from = $(this).attr('data_from');
            let to = $(this).attr('data_to');

            if (localStorage.getItem('working_hoursedit') === null || localStorage.getItem('working_hoursedit') === [] || localStorage.getItem('working_hoursedit') === undefined) {
                console.log('here');
            } else {
                var working_hours = JSON.parse(localStorage.getItem('working_hoursedit'));
                working_hours.forEach(function (time, index) {
                    if (time.day == day && time.from == from && time.to == to) {
                        working_hours.splice(index, 1); // first element removed
                        localStorage.setItem('working_hoursedit', JSON.stringify(working_hours));
                    }
                });
                console.log(JSON.parse(localStorage.getItem('working_hoursedit')));
            }
        });

    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
    <script>
        $(document).on('click', '.providerdraw', function () {
            $('#amount_error').empty();
            $('#provider_id_error').empty();
            $('#appendGift').empty();
            $('#giftsContainer').hide();
            $('#submitBtn').prop('disabled', true);
            $('#usersContainer').hide();
            $.ajax({
                type: 'post',
                url: "{{route('admin.lotteries.loadGifts')}}",
                data: {
                    'provider_id': $(this).val(),
                    'amount': $('#amountOfGifts').val(),
                },
                success: function (data) {
                    $('#giftsContainer').show();
                    $('#appendGift').empty().append(data.content);
                }, error: function (reject) {
                    $('#appendGift').empty();
                    $('#giftsContainer').hide();
                    let errors = $.parseJSON(reject.responseText)
                    $.each(errors, function (key, val) {
                        $("#" + key + "_error").text(val[0]);
                    });
                }
            });
        });

        $(document).on('click', '.giftdraw', function () {
            $('#submitBtn').prop('disabled', false);
            $('#usersContainer').hide();
        });

        $(document).on('click', '#submitBtn', function (e) {

            $('#amount_error').empty();
            $('#provider_id_error').empty();
            $('#usersContainer').hide();
            $('#loader').show();

            $(this).prop('disabled', true);
            let provider_id = $("input:radio.providerdraw:checked").val();
            let gift_id = $("input:radio.giftdraw:checked").val();
            let amount = $('#amountOfGifts').val();

            $.ajax({
                type: 'post',
                url: "{{route('admin.lotteries.loadUsers')}}",
                data: {
                    'provider_id': provider_id,
                    'gift_id': gift_id,
                    'amount': amount
                },
                success: function (data) {
                    setTimeout(function () {
                        $('#usersContainer').show();
                        $('#appendUsers').empty().append(data.content);
                        $('#submitBtn').prop('disabled', false);
                        $('#loader').hide();
                    },3000)

                }, error: function (reject) {

                    $('#loader').hide();
                    $('#appendUsers').empty();
                    $('#usersContainer').hide();
                    $('#submitBtn').prop('disabled', false);
                    let errors = $.parseJSON(reject.responseText)
                    $.each(errors, function (key, val) {
                        $("#" + key + "_error").text(val[0]);
                    });
                }
            });

        });
    </script>
</div>
</body>
</html>


