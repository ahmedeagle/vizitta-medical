@extends('layouts.master')

@section('title', 'أضافه فلتر للعروض')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('add.filter') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> أضافه فلتر للعروض </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => 'admin.offers.filters.store', 'class' => 'form' ]) }}
        @include('offers.filters.form', ['btn' => 'حفظ'])
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
