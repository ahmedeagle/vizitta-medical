@extends('layouts.master')

@section('title', 'تعديل سبب')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('edit.reasons') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-pencil"></i> تعديل سبب </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::model($reason, ['route' => ['admin.reasons.update' , $reason->id], 'class' => 'form', 'method' => 'PUT']) }}
                @include('reasons.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
            {{ Form::close() }}
        </div>
    </div>
@stop
