@extends('layouts.master')

@section('title', 'عرض مقدم الخدمة')

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
        .widget-box{
            min-height: 100px !important;
        }
    </style>
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('view.user.message') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-image"></i> رسالة رقم {{ $message->message_no }}</h1>
            </div>
        </div>

        <div class="col-sm-12">
            <div id="user-profile-1" class="user-profile row">
                <div class="col-sm-12 right buttons">
                    <span class="btn btn-app btn-lg btn-danger no-hover">
                        <span class="line-height-1 bigger-170 white icon">
                            <a href="#" data-toggle="modal" data-target="#{{$message->id}}">
                                <i class="ace-icon fa fa-close white"></i>
                            </a>
                        </span>
                        <br>
                        <span class="line-height-1 smaller-90">
                            <a class="white" href="#" data-toggle="modal"
                               data-target="#{{$message->id}}">مسح</a>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="profile-user-info profile-user-info-striped">
                <div class="profile-info-row">
                    <div class="profile-info-name">رقم الرسالة</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $message->message_no }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">اسم المستخدم</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $message->user ? $message->user->name : "" }}</span>
                    </div>
                </div>

                <div class="profile-info-row full">
                    <div class="profile-info-name">الرساله</div>
                    <div class="profile-info-value">
                        <span class="editable">{!! $message->title !!}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">الأهمية</div>
                    <div class="profile-info-value">
                        <span class="editable">
                            @if($message->importance == 1)
                                مستعجلة
                            @elseif($message->importance == 2)
                                عادية
                            @endif
                        </span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">النوع</div>
                    <div class="profile-info-value">
                        <span class="editable">
                            @if($message->type == 1)
                                استفسار
                            @elseif($message->type == 2)
                                اقتراح
                            @elseif($message->type == 3)
                                شكوى
                            @elseif($message->type == 4)
                                غير ذلك
                            @endif
                        </span>
                    </div>
                </div>


                <div class="profile-info-row">
                    <div class="profile-info-name">تاريخ الرسالة</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ date('H:i Y-m-d', strtotime($message->created_at)) }}</span>
                    </div>
                </div>
            </div>
            <div class="space-12"></div>

            <div class="col-sm-12 widget-box transparent">
                <div class="widget-header widget-header-small">
                    <h4 class="widget-title blue smaller">
                        <i class="ace-icon fa fa-whatsapp bigger-110"></i>
                        الردود
                    </h4>
                </div>
                <div class="space-10"></div>


                <div class="timeline-container">
                    <div class="timeline-items">
                        @foreach($replies as $reply)
                            <div class="timeline-item {{ $reply->FromUser ==  0 ? "" : "reply" }} clearfix">
                                <div class="timeline-info">
                                    <i class="timeline-indicator ace-icon btn fa {{ $reply->FromUser == 0 ? "fa-user-secret btn-success" : "fa-user btn-inverse" }} no-hover"></i>
                                </div>
                                <div class="widget-box transparent">
                                    <div class="widget-header widget-header-small">
                                        <h5 class="widget-title smaller">{{ $reply->FromUser == 0 ? "أنت" : "المستخدم" }}</h5>

                                        <span class="widget-toolbar">
                                        <i class="ace-icon fa fa-clock-o bigger-110"></i>
                                        {{ date('H:i Y-m-d', strtotime($reply->created_at)) }}
                                    </span>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="clearfix">
                                                <div class="pull-right">
                                                    {!! $reply->message !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>



                <div class="col-sm-12 widget-box transparent">
                    <div class="widget-header widget-header-small">
                        <h4 class="widget-title blue smaller">
                            <i class="ace-icon fa fa-plus bigger-110"></i>
                            إضافة رد
                        </h4>
                    </div>
                    <div class="space-10"></div>

                    {{ Form::open(['route' => 'admin.user.message.reply', 'class' => 'form']) }}
                        {{ Form::hidden('ticket_id', $message->id) }}
                        <div class="form-group col-sm-12">
                            {{ Form::textarea('message', old('message'), [ 'required' => 'required', 'class' => 'form-control ' . ($errors->has('message') ? 'redborder' : '') ]) }}
                            <small class="text-danger">{{ $errors->has('message') ? $errors->first('message') : '' }}</small>
                        </div>

                        <div class="form-group col-sm-12 submit">
                            {{ Form::submit('حفظ', ['class' => 'btn btn-sm']) }}
                        </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@stop

@section('popup')
    <p>من فضلك إدخل جميع الحقول المطلوبة</p>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $("textarea").each(function() {
                var editor = CKEDITOR.replace($(this).attr('id'), {
                    language: 'ar',
                }).on('required', function( evt ) {
                    $('.hover_popup').show();
                    evt.cancel();
                });
            });
        });
    </script>
@stop
