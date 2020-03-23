@extends('layouts.master')

@section('title', 'عرض المستخدم')

@section('styles')
    {!! Html::style('css/colorbox.min.css') !!}
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
        {!! Breadcrumbs::render('view.user') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-image"></i> {{ $user->name }}</h1>
            </div>
        </div>

        <div class="col-sm-12">
            <div id="user-profile-1" class="user-profile row">
                <div class="col-sm-12 right buttons">
                    @if($user->status)
                        <span class="btn btn-app btn-lg btn-danger no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="{{ route('admin.user.status', [$user->id, 0]) }}">
                                    <i class="ace-icon fa fa-times white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.user.status', [$user->id, 0]) }}">إلغاء التفعيل</a>
                            </span>
                        </span>
                    @else
                        <span class="btn btn-app btn-lg btn-success no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="{{ route('admin.user.status', [$user->id, 1]) }}">
                                    <i class="ace-icon fa fa-check white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.user.status', [$user->id, 1]) }}">تفعيل</a>
                            </span>
                        </span>
                    @endif

                    @if(count($user->reservations) == 0)
                        <span class="btn btn-app btn-lg btn-danger no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="#" data-toggle="modal" data-target="#{{$user->id}}">
                                    <i class="ace-icon fa fa-close white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.user.viewdelete', $user->id) }}" {{--data-toggle="modal"
                                   data-target="#{{$user->id}}" --}}>مسح</a>
                            </span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="profile-user-info profile-user-info-striped">
                <div class="profile-info-row">
                    <div class="profile-info-name">الإسم</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user->name }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">رقم الجوال</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user->mobile }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">رقم الهوية</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user->id_number }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">البريد الإلكترونى</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user->email }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">العنوان</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user->address }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">تاريخ الميلاد</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user->birh_date }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">المدينة</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user->city ? $user->city->name_ar : "" }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">شركة التأمين</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user->insuranceCompany ? $user->insuranceCompany->name_ar : "" }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">الجنس </div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $user-> getGender() }}</span>
                    </div>
                </div>


                <div class="profile-info-row full">
                    <div class="profile-info-name">الدكاترة المفضليين</div>
                    <div class="profile-info-value">
                        <span class="editable">
                            <ul class="floating">
                                @foreach($favoriteDoctors as $favorite)
                                    <li><a href="{{ route('admin.doctor.view', $favorite->doctor_id) }}">{{ $favorite->doctor ? $favorite->doctor->name_ar : "" }}</a></li>
                                @endforeach
                            </ul>
                        </span>
                    </div>
                </div>

                <div class="profile-info-row full">
                    <div class="profile-info-name">مقدمى الخدمة المفضليين</div>
                    <div class="profile-info-value">
                        <span class="editable">
                            <ul class="floating">
                                @foreach($favoriteProviders as $favorite)
                                    <li><a href="{{ route('admin.provider.view', $favorite->provider_id) }}">{{ $favorite->provider ? $favorite->provider->name_ar : "" }}</a></li>
                                @endforeach
                            </ul>
                        </span>
                    </div>
                </div>

                <div class="profile-info-row full">
                    <div class="profile-info-name">صوره التأمين</div>
                    <div class="profile-info-value">
                        <span class="editable">
                            @if(!empty($user->insurance_image))
                                <ul class="ace-thumbnails clearfix">
                                    <li>
                                        <a href="{{ asset($user->insurance_image) }}" data-rel="colorbox">
                                            <img style="width:150px" width="150" height="150" alt="150x150" src="{{ asset($user->insurance_image) }}" />
                                            <div class="text">
                                                <div class="inner">عرض صوره التأمين</div>
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                            @endif
                        </span>
                    </div>
                </div>

                <div class="profile-info-row full">
                    <div class="profile-info-name">الحجوزات</div>
                    <div class="profile-info-value">
                        <span class="editable">
                            <ul class="floating">
                                @foreach($reservations as $reservation)
                                    <li><a href="{{ route('admin.reservation.view', $reservation->id) }}">{{ $reservation->reservation_no }}</a></li>
                                @endforeach
                            </ul>
                        </span>
                    </div>
                </div>

                <div class="profile-info-row full">
                    <div class="profile-info-name">السجلات الطبية</div>
                    <div class="profile-info-value">
                        <span class="editable">
                            @foreach($records as $record)
                                <div class="well">
                                    <p><b>التخصص:</b> {{ $record->specification ? $record->specification->name_ar : "" }}</p>
                                    <p><b>اليوم:</b> {{ $record->day_date }}</p>
                                    <p><b>الملخص:</b> {!! $record->summary !!}</p>
                                    <p><b>المرفقات:</b>
                                        <ul class="ace-thumbnails clearfix">
                                            @foreach($record->attachments as $attachment)
                                                @if(!empty($attachment->attachment))
                                                    <li>
                                                        <a href="{{ asset($attachment->attachment) }}" data-rel="colorbox">
                                                            <img style="width:150px" width="150" height="150" alt="150x150" src="{{ asset($attachment->attachment) }}" />
                                                            <div class="text">
                                                                <div class="inner">عرض الملف</div>
                                                            </div>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                       </ul>
                                    </p>
                                </div>
                            @endforeach
                        </span>
                    </div>
                </div>
            </div>
            <div class="space-12"></div>
        </div>
    </div>
@stop

@section('scripts')
    {!! Html::script('js/jquery.colorbox.min.js') !!}
    <script type="text/javascript">
        jQuery(function($) {
            var $overflow = '';
            var colorbox_params = {
                rel: 'colorbox',
                reposition:true,
                scalePhotos:true,
                scrolling:false,
                previous:'<i class="ace-icon fa fa-arrow-left"></i>',
                next:'<i class="ace-icon fa fa-arrow-right"></i>',
                close:'&times;',
                current:'{current} of {total}',
                maxWidth:'100%',
                maxHeight:'100%',
                onOpen:function(){
                    $overflow = document.body.style.overflow;
                    document.body.style.overflow = 'hidden';
                },
                onClosed:function(){
                    document.body.style.overflow = $overflow;
                },
                onComplete:function(){
                    $.colorbox.resize();
                }
            };
            $('.ace-thumbnails [data-rel="colorbox"]').colorbox(colorbox_params);
            $("#cboxLoadingGraphic").html("<i class='ace-icon fa fa-spinner orange fa-spin'></i>");
            $(document).one('ajaxloadstart.page', function(e) {
                $('#colorbox, #cboxOverlay').remove();
            });
        })
    </script>
@stop
