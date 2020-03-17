@extends('layouts.master')

@section('title', 'إضافة حى')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('add.district') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-magic"></i> إضافة حى </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => 'admin.district.store', 'class' => 'form']) }}
            @include('district.form', ['btn' => 'حفظ'])
        {{ Form::close() }}
    </div>
</div>
@stop
