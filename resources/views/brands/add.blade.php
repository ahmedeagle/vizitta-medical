@extends('layouts.master')

@section('title', 'إضافة  شعار جديد')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('add.brands') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-magic"></i> إضافة  شعار جديد </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::open(['route' => 'admin.brands.store', 'class' => 'form' ,'files' =>true]) }}
            @include('brands.form', ['btn' => 'حفظ'])
            {{ Form::close() }}
        </div>
    </div>
@stop
