@extends('layouts.master')

@section('title', 'تعديل  قسم ')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.offerCategories') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل قسم </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::model($category, ['route' => ['admin.offerCategories.update' , $category->id], 'files'=>true ,'class' => 'form', 'method' => 'PUT']) }}
        @include('offerCategories.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
        {{ Form::close() }}
    </div>
</div>
@stop
