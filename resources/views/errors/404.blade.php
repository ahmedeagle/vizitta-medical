@extends('layouts.404-layout')

@section('title', 'الصفحة غير موجودة')

@section('content')
    <div class="page-content">
        <div class="row">
            <div class="col-xs-12">
                <!-- PAGE CONTENT BEGINS -->
                <div class="error-container">
                    <div class="well">
                        <h1 class="grey lighter smaller">
                        <span class="blue bigger-125">
                            <i class="ace-icon fa fa-sitemap"></i>
                            404
                        </span>
                            الصفحة غير موجودة
                        </h1>
                        <hr/>
                        <div>
                            <div class="space"></div>
                            <h4 class="smaller">جرّب واحداً من الحلول التالية:</h4>

                            <ul class="list-unstyled spaced inline bigger-110 margin-15">
                                <li>
                                    <i class="ace-icon fa fa-hand-o-right blue"></i>
                                    أعد التحقق من عنوان URL لمعرفة الأخطاء المطبعية
                                </li>
                                <li>
                                    <i class="ace-icon fa fa-hand-o-right blue"></i>
                                    أخبر الإدارة عن العطل
                                </li>
                            </ul>
                        </div>
                        <hr/>
                        <div class="space"></div>
                        <div class="center">
                            <a href="javascript:history.back()" class="btn btn-grey">
                                <i class="ace-icon fa fa-arrow-left"></i>
                                الرجوع
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop