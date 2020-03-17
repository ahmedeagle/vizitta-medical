@extends('layouts.master')

@section('title', 'تعديل رصيد فرع  ')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.provider.balance') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل رصيد - {{ $provider->name_ar }} </h1>
        </div>
    </div>

    <div class="col-md-12">
        <h5 class="widget-title smaller"><i class="ace-icon fa fa-credit-card"></i> برجاء كتابة  متبقي الرصيد بعد خصم قيمه  المحصل من الفرع   هذا </h5>
        {{ Form::model($provider,['route' => ['admin.data.provider.balance.update', $provider->id], 'class' => 'form', 'method' => 'PUT']) }}
        <div class="form-group has-float-label col-sm-12">
            <label for="balance">الرصيد   <span class="astric">*</span></label>
            {{ Form::number('balance', old('balance'), ['placeholder' => 'الرصيد الحالي   ', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('balance') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('balance') ? $errors->first('balance') : '' }}</small>
        </div>

        <div class="form-group col-sm-12 submit">
            {{ Form::submit('حفظ', ['class' => 'btn btn-sm']) }}
        </div>
        {{ Form::close() }}
    </div>
</div>
@stop
