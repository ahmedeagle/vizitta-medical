@extends('layouts.master')

@section('title', 'تعديل  الطبيب ')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet"/>
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.doctor') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل الطبيب </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::model($doctor, ['route' => ['admin.doctor.update' , $doctor->id], 'class' => 'form doctorForm', 'method' => 'PUT', 'files' => true]) }}
        @include('doctor.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
        {{ Form::close() }}
    </div>
</div>
@stop

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
    <script>

        /*  $(".availableDay").change(function() {
             if(this.checked) {
                   val = $(this).val();
                   $('.'+val).prop('disabled', false);
               }else{
                 val = $(this).val();
                 $('.'+val).prop('disabled',true);

               }
         });
 */
        $(document).ready(function () {
            $('.js-example-basic-single').select2();
            $('.js-example-basic-multiple').select2();
        });



    </script>
@stop
