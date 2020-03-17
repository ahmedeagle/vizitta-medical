@extends('layouts.master')

@section('title', 'تعديل نص الإتفاقية')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.information') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل الملف الشخصى </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::model($appData, ['route' => 'admin.data.information.update', 'class' => 'form', 'method' => 'PUT']) }}
        <div class="form-group has-float-label col-sm-12">
            <label for="email">البريد الإلكترونى <span class="astric">*</span></label>
            {{ Form::email('email', old('email'), ['placeholder' => 'البريد الإلكترونى', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('email') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('email') ? $errors->first('email') : '' }}</small>
        </div>

        <div class="form-group has-float-label col-sm-12">
            <label for="mobile">رقم الجوال <span class="astric">*</span></label>
            {{ Form::text('mobile', old('mobile'), ['placeholder' => 'رقم الجوال', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('mobile') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('mobile') ? $errors->first('mobile') : '' }}</small>
        </div>

        <div class="form-group has-float-label col-sm-12">
            <label for="password">كلمة المرور</label>
            {{ Form::password('password', old('password'), ['placeholder' => 'كلمة المرور', 'class' => 'form-control ' . ($errors->has('password') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('password') ? $errors->first('password') : '' }}</small>
        </div>

        <div class="form-group col-sm-12 submit">
            {{ Form::submit('حفظ', ['class' => 'btn btn-sm']) }}
        </div>
        {{ Form::close() }}
    </div>
</div>
@stop
