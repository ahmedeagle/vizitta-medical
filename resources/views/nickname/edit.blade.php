@extends('layouts.master')

@section('title', 'تعديل لقب')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('edit.nickname') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-pencil"></i> تعديل لقب </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::model($nickname, ['route' => ['admin.nickname.update' , $nickname->id], 'class' => 'form', 'method' => 'PUT']) }}
                @include('nickname.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
            {{ Form::close() }}
        </div>
    </div>
@stop