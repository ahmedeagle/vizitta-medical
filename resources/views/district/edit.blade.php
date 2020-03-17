@extends('layouts.master')

@section('title', 'تعديل حى')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('edit.district') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-pencil"></i> تعديل حى </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::model($district, ['route' => ['admin.district.update' , $district->id], 'class' => 'form', 'method' => 'PUT']) }}
            @include('district.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
            {{ Form::close() }}
        </div>
    </div>
@stop