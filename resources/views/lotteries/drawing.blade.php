@extends('layouts.master')

@section('title', 'السحب والهدايا ')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet"/>
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('drawing') !!}
@stop

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
                                           value="{{$provider -> id}}">  {{$provider -> name_ar}}
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

            <br> <br>
            <div class="form-group col-sm-12 submit text-center" style="padding-top: 60px;">
                <button class="btn btn-danger btn-lg" id="submitBtn" type="button" disabled> أجراء عملية السحب</button>
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
@stop

@section('scripts')
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
                    $('#usersContainer').show();
                    $('#appendUsers').empty().append(data.content);
                    $('#submitBtn').prop('disabled', false);
                }, error: function (reject) {
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
@stop
