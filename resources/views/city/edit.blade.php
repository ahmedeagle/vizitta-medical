@extends('layouts.master')

@section('title', 'تعديل مدينة')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.city') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل مدينة </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::model($city, ['route' => ['admin.city.update' , $city->id], 'class' => 'form', 'method' => 'PUT']) }}
            @include('city.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
        {{ Form::close() }}
    </div>
</div>
@stop