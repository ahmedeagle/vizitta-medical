@extends('layouts.master')

@section('title', 'نص الإتفاقية وشروط الحجز')

@section('styles')
    <style>
        .profile-picture {
            border: 0 !important;
            box-shadow: none !important;
        }
        .icon {
            margin-left: 5px;
        }
        .disabled a {
            background-color: #b1b1b1 !important;
        }
    </style>
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('agreement') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-file-text"></i> نص الإتفاقية وشروط الحجز </h1>
        </div>
    </div>

    <div class="col-sm-12">
        <div id="user-profile-1" class="user-profile buttons row">
            <div class="col-sm-12 center">
                <span class="btn btn-app btn-lg btn-primary no-hover">
                    <span class="line-height-1 bigger-170 white icon">
                        <a href="{{ route('admin.data.agreement.edit') }}">
                            <i class="ace-icon fa fa-pencil white"></i>
                        </a>
                    </span>
                    <br>
                    <span class="line-height-1 smaller-90">
                        <a class="white" href="{{ route('admin.data.agreement.edit') }}">تعديل</a>
                    </span>
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-small">
                <h4 class="widget-title smaller">
                    <i class="ace-icon fa fa-check-square-o bigger-110"></i>
                    نص الإتفاقيه بالعربية
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    {!! @$agreement->agreement_ar !!}
                </div>
            </div>
        </div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-small">
                <h4 class="widget-title smaller">
                    <i class="ace-icon fa fa-check-square-o bigger-110"></i>
                    نص الإتفاقية بالإنجليزيه
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    {!! @$agreement->agreement_en !!}
                </div>
            </div>
        </div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-small">
                <h4 class="widget-title smaller">
                    <i class="ace-icon fa fa-check-square-o bigger-110"></i>
                    شروط الحجز بالعربية
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    {!! @$agreement->reservation_rules_ar !!}
                </div>
            </div>
        </div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-small">
                <h4 class="widget-title smaller">
                    <i class="ace-icon fa fa-check-square-o bigger-110"></i>
                    شروط تسجيل مقدم الخدمة  بالعربية
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    {!! @$agreement->provider_reg_rules_ar !!}
                </div>
            </div>
        </div>

        <div class="widget-box transparent">
            <div class="widget-header widget-header-small">
                <h4 class="widget-title smaller">
                    <i class="ace-icon fa fa-check-square-o bigger-110"></i>
                      شروط تسجيل مقدم الخدمة بالانجليزية
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    {!! @$agreement->provider_reg_rules_en !!}
                </div>
            </div>
        </div>

        <div class="space-12"></div>
    </div>
</div>
@stop
