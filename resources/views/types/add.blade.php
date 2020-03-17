@extends('layouts.master')
@section('title', 'إضافة  نوع مقدم خدمة ')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('add.types') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                  <h1><i class="menu-icon fa fa-magic"></i> إضافة  نوع مقدم خدمة جديد  </h1>
             </div>
        </div>

        <div class="col-md-12">
             {{ Form::open(['route' => 'admin.types.store', 'class' => 'form']) }}
                @include('types.form', ['btn' => 'حفظ'])
             {{ Form::close() }}
        </div>
    </div>
@stop
