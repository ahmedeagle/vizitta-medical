@extends('layouts.master')

@section('title', 'إضافة سبب رفض ')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('add.reasons') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-magic"></i> إضافة  سبب رفض جديد  </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::open(['route' => 'admin.reasons.store', 'class' => 'form']) }}
                @include('reasons.form', ['btn' => 'حفظ'])
            {{ Form::close() }}
        </div>
    </div>
@stop
