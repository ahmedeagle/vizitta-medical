@extends('layouts.master')

@section('title', 'تعديل شركة تأمين')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('edit.company.insurance') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-pencil"></i> تعديل شركة تأمين </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::model($company, ['route' => ['admin.insurance.company.update' , $company->id], 'class' => 'form', 'method' => 'PUT', 'files' => true]) }}
                @include('insurance-company.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
            {{ Form::close() }}
        </div>
    </div>
@stop