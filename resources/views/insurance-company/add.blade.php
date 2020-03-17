@extends('layouts.master')

@section('title', 'إضافة شركة تأمين')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('add.company.insurance') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-magic"></i> إضافة شركة تأمين </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::open(['route' => 'admin.insurance.company.store', 'class' => 'form', 'files' => true]) }}
                @include('insurance-company.form', ['btn' => 'حفظ'])
            {{ Form::close() }}
        </div>
    </div>
@stop
