@extends('layouts.master')

@section('title', ' أعدادات مشاركه التطبيق ')

@section('styles')
    {!! Html::style('css/form.css') !!}
    <style>
        .fade {
            opacity: 0;
            -webkit-transition: opacity .15s linear;
            -o-transition: opacity .15s linear;
            transition: opacity .15s linear;
        }

        .custom-control {
            position: relative;
            display: block;
            min-height: 1.5rem;
            padding-left: 1.5rem;
        }

        .custom-control-label {
            position: relative;
            margin-top: 15px;
            vertical-align: top;
        }
    </style>
@stop

@section('content')
@section('breadcrumbs')

    {!! Breadcrumbs::render('sharing') !!}

@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-magic"></i> أعدادات مشاركه التطبيق </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => ['admin.sharing.update'],'method' => 'POST', 'class' => 'form']) }}


        <div class="col-sm-12">

            <div class="row">
                <div class="col-md-6">
                    <label class="checkbox-inline" style="user-select: none">
                        <input style="margin: -8px -19px 0 0 " type="checkbox" value="1" name="active_owner_points"
                               id="owner_check" {{  old('active_owner_points') == 1 ? ' checked' : '' }} @if(@$settings -> owner_points >= 0  ) checked @endif>
                        تفعيل نقاط لصاحب الكود
                    </label>
                </div>

                <div class="col-md-6">
                    <label class="checkbox-inline" style="user-select: none">
                        <input style="margin: -8px -19px 0 0 " type="checkbox" value="1" name="active_invited_points"
                               id="invited_check" {{  old('active_invited_points') == 1 ? ' checked' : '' }}  @if(@$settings -> invited_points >= 0  ) checked @endif>
                        تفعيل نقاط للمسجل عن طريق الكود
                    </label>
                </div>
            </div>
            <small class="text-danger"></small>
        </div>

        <br>
        <div class="form-group has-float-label col-sm-6">
            <div  style="display: none" id="owner_points">
            <input name="owner_points" placeholder='نقاط مالك الكود'
                   class="form-control {{$errors->has('owner_points') ? 'redborder' : ''}}" value="{{old('owner_points',$settings -> owner_points)}}"  >
            <label for="owner_points">نقاط مالك الكود <span class="astric">*</span></label>
            <small class="text-danger">{{ $errors->has('owner_points') ? $errors->first('owner_points') : '' }}</small>
            </div>
        </div>

        <div class="form-group has-float-label col-sm-6" style="display: none" id="invited_points">
            <input name="invited_points" placeholder='نقاط مالك الكود'
                   class="form-control {{$errors->has('invited_points') ? 'redborder' : ''}}" value="{{old('invited_points',$settings -> invited_points)}}">

            <label for="invited_points"> نقاط المدعو <span class="astric">*</span></label>
            <small
                class="text-danger">{{ $errors->has('invited_points') ? $errors->first('invited_points') : '' }}</small>
        </div>

        <div class="form-group col-sm-12 submit">
            {{ Form::submit('حفظ', ['class' => 'btn btn-sm' ]) }}
        </div>
        {{ Form::close() }}
    </div>
</div>

@stop


@section('scripts')

    <script>
        $(document).ready(function () {
            if ($('#owner_check').is(":checked")) {
                $('#owner_points').show();
            } else {
                $('#owner_points').hide();
            }
            if ($('#invited_check').is(":checked")) {
                $('#invited_points').show();
            } else {
                ('#invited_points').hide();
            }
        });

        $(document).on('click', '#owner_check', function () {
            if ($('#owner_check').is(":checked")) {
                $('#owner_points').show();
            } else {
                $('#owner_points').hide();
            }
        });

        $(document).on('click', '#invited_check', function () {

            if ($('#invited_check').is(":checked")) {
                $('#invited_points').show();
            } else {
                $('#invited_points').hide();
            }
        });
    </script>
@stop
