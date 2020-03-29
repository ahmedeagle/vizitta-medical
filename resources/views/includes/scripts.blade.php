<script src="https://js.pusher.com/5.1/pusher.min.js"></script>
{!! Html::script('bower_components/jquery/js/jquery.min.js') !!}
{!! Html::script('bower_components/jquery-ui/js/jquery-ui.min.js') !!}
{!! Html::script('bower_components/popper.js/js/popper.min.js') !!}
{!! Html::script('bower_components/bootstrap/js/bootstrap.min.js') !!}
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

    var pusher = new Pusher('b0e07cc9a12d705c6b4d', {
        encrypted: false
    });

</script>
<script src="{{asset('js/pusherNewReservation.js')}}"></script>
<script src="{{asset('js/pusherNewRate.js')}}"></script>
<script src="{{asset('js/pusherUserEditReservationTime.js')}}"></script>
<script src="{{asset('js/pusherProviderEditReservationTime.js')}}"></script>

<script>
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

@yield('scripts')
@yield('extra_scripts')
@include('flashy::message')
