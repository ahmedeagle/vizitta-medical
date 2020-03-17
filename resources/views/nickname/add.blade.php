@extends('layouts.master')

@section('title', 'إضافة لقب')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('add.nickname') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-magic"></i> إضافة لقب </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::open(['route' => 'admin.nickname.store', 'class' => 'form']) }}
            @include('nickname.form', ['btn' => 'حفظ'])
            {{ Form::close() }}
        </div>
    </div>
@stop
