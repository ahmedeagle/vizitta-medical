@extends('layouts.master')

@section('title', ' أضافة طبيب  ')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet" />
    {!! Html::style('css/form.css') !!}

@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.doctor') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> اضافه طبيب  </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => ['admin.doctor.store'],'class' => 'doctorForm form', 'method' => 'post', 'files' => true]) }}
            @include('doctor.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
        {{ Form::close() }}
    </div>
</div>
@stop

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
    <script>
   /*     $('.select2').select2({allowClear:true});
        $('#select2-multiple-style .btn').on('click', function(e){
            var target = $(this).find('input[type=radio]');
            var which = parseInt(target.val());
            if(which == 2) $('.select2').addClass('tag-input-style');
            else $('.select2').removeClass('tag-input-style');
        });
        $("select[data-use-select2]").each(function() {
            $(this).select2({
                theme: "bootstrap4",
                dropdownParent: $(this).parent()
            });
        });*/
         $(".availableDay").prop("checked", false);
        $(".availableDay").change(function() {
            if(this.checked) {
                  val = $(this).val();
                  $('.'+val).prop('disabled', false);
              }else{
                $('.'+val).prop('disabled',true);

              }
        });

   $(document).ready(function() {
       $('.js-example-basic-single').select2();
       $('.js-example-basic-multiple').select2();
   });

    </script>
@stop
