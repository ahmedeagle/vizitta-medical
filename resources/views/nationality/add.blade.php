@extends('layouts.master')

@section('title', 'إضافة جنسية')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('add.nationality') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-magic"></i> إضافة جنسية </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => 'admin.nationality.store', 'class' => 'form']) }}
            @include('nationality.form', ['btn' => 'حفظ'])
        {{ Form::close() }}
    </div>
</div>
@stop
