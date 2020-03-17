@extends('layouts.master')

@section('title', 'إضافة صفحة فرعية')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('add.customPage') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-magic"></i> إضافة صفحة فرعية </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::open(['route' => 'admin.customPage.store', 'class' => 'form']) }}
                @include('customPage.form', ['btn' => 'حفظ'])
            {{ Form::close() }}
        </div>
    </div>
@stop

@section('popup')
    <p>من فضلك إدخل جميع الحقول المطلوبة</p>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $("textarea").each(function() {
                var editor = CKEDITOR.replace($(this).attr('id'), {
                    language: 'ar',
                }).on('required', function( evt ) {
                    $('.hover_popup').show();
                    evt.cancel();
                });
            });
        });
    </script>
@stop