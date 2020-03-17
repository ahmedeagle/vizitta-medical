@extends('layouts.master')

@section('title', 'تعديل تخصص')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('edit.specification') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-pencil"></i> تعديل تخصص </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::model($specification, ['route' => ['admin.specification.update' , $specification->id], 'class' => 'form', 'method' => 'PUT']) }}
                @include('specification.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
            {{ Form::close() }}
        </div>
    </div>
@stop