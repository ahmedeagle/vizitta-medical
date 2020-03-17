@extends('layouts.master')

@section('title', 'تعديل  مستخدم ')

@section('styles')
    {!! Html::style('css/select2.min.css') !!}
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.admins') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل  مستخدمي اللوحة  </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::model($admin, ['route' => ['admin.admin.update' , $admin->id], 'class' => 'form', 'method' => 'PUT', 'files' => true]) }}
        @include('admins.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
        {{ Form::close() }}
    </div>
</div>
@stop

@section('scripts')
    <script>
       $(document).ready(function
           (){
                $('#permission_password').val('');
       })
    </script>
    @stop

