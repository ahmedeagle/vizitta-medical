@extends('layouts.master')

@section('title', 'اضافة  مستخدم ')

@section('styles')
    {!! Html::style('css/select2.min.css') !!}
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('add.admins') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i>  اضافة   مستخدم جديد    </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => ['admin.admins.store'], 'class' => 'form', 'method' => 'post', 'files' => true]) }}
        @include('admins.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
        {{ Form::close() }}
    </div>
</div>
@stop

