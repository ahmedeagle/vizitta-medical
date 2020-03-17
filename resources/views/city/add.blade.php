@extends('layouts.master')

@section('title', 'إضافة مدينة')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('add.city') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-magic"></i> إضافة مدينة </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::open(['route' => 'admin.city.store', 'class' => 'form']) }}
                @include('city.form', ['btn' => 'حفظ'])
            {{ Form::close() }}
        </div>
    </div>
@stop
