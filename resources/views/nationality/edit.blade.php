@extends('layouts.master')

@section('title', 'تعديل جنسية')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('edit.nationality') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-pencil"></i> تعديل جنسية </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::model($nationality, ['route' => ['admin.nationality.update' , $nationality->id], 'class' => 'form', 'method' => 'PUT']) }}
            @include('nationality.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
            {{ Form::close() }}
        </div>
    </div>
@stop