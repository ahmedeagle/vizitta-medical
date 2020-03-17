@extends('layouts.master')

@section('title', 'إضافة رمز')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet"/>
    {!! Html::style('css/form.css') !!}

    <style>
        .select2-container .select2-search--inline {
            float: right;
            text-align: center;
            position: fixed;
         }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered{
            padding-top: 5px;
        }
    </style>
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('add.promoCode') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-magic"></i> إضافة رمز </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => 'admin.promoCode.store', 'class' => 'form' , 'files' => true]) }}
        @include('promoCode.form', ['btn' => 'حفظ'])
        {{ Form::close() }}
    </div>
</div>
@stop


@section('extra_scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>

    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        //get provider branches
        $(document).on('change', '#providers', function (e) {
            e.preventDefault();
            $.ajax({

                type: 'post',
                url: "{{Route('admin.promoCode.providerbranches')}}",
                data: {
                    'parent_id': $(this).val(),
                    //'_token'   :   $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (data) {
                    $('.appendbrnaches').empty().append(data.content);
                }
            });
        });

        //get branch doctors
        $(document).on('change', '#branches', function (e) {
            e.preventDefault();
            $.ajax({

                type: 'post',
                url: "{{Route('admin.promoCode.brancheDoctors')}}",
                data: {
                    'branche_id': $(this).val(),
                },

                success: function (data) {
                    $('.appenddoctors').empty().append(data.content);
                }

            });

        });

        $(document).ready(function () {
            $(".artextarea").each(function () {
                var editor = CKEDITOR.replace($(this).attr('id'), {
                    language: 'ar',
                });
            });

            $(".entextarea").each(function () {
                var editor = CKEDITOR.replace($(this).attr('id'), {
                    language: 'en',
                });
            });
        });

        $(document).on('change', '#cop_type', function () {

            if ($(this).val() == 2) {
                $('#cop_price').show();
                $('#cop_percg').show();
                $('#cop_discount').hide();
                $('#copounCode').hide();
                $('#app_perc').hide();
            } else {
                $('#cop_price').hide();
                $('#cop_percg').hide();
                $('#cop_discount').show();
                $('#copounCode').show();
                $('#app_perc').show();
            }
        });

        $(document).ready(function () {
            $('.js-example-basic-single').select2();
            $('.js-example-basic-multiple').select2();
        });

    </script>
@stop
