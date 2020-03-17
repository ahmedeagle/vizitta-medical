@extends('layouts.master')

 @section('title', 'تعديل  نوع مقدم خدمة ')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
         {!! Breadcrumbs::render('edit.types') !!}

     @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                 <h1><i class="menu-icon fa fa-pencil"></i> تعديل مقدم خدمة  </h1>
              </div>
        </div>

        <div class="col-md-12">
             {{ Form::model($type, ['route' => ['admin.types.update' , $type->id], 'class' => 'form', 'method' => 'PUT']) }}
                @include('types.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
            {{ Form::close() }}
        </div>
    </div>
@stop


