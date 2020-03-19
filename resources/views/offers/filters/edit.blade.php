@extends('layouts.master')

@section('title', 'تعديل فلتر عروض')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('edit.branch') !!}
    @stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل فلتر عروض </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::model($filter, ['route' => ['admin.offers.filters.update' , $filter->id], 'class' => 'form', 'method' => 'POST']) }}
            @include('offers.filters.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
        {{ Form::close() }}
    </div>
</div>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            let operation = $('#operation').val();
            if (operation == 0 || operation == 1 || operation == 2) {
                $('#price').show();
            } else {
                $('#price').hide();
            }
        });

        $(document).on('change', '#operation', function () {
            let operation = $('#operation').val();
            if (operation == 0 || operation == 1 || operation == 2) {
                $('#price').show();
            } else {
                $('#price').hide();
            }
        });
    </script>
@stop
