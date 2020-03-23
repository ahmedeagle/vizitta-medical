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
    </style>
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('view.doctor') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-image"></i> {{ $doctor->name_ar }}</h1>
            </div>
        </div>

        <div class="col-sm-12">
            <div id="user-profile-1" class="user-profile row">
                <div class="col-xs-12 col-sm-3 center">
                    <div>
                        <div class="profile-picture">
                            <img id="avatar" class="editable img-responsive" alt="Icon URL"
                                 src="{{ asset($doctor->photo ? $doctor->photo : 'images/no_image.png') }}"/>
                        </div>
                    </div>
                    <div class="space-10"></div>
                </div>

                <div class="col-sm-9 center">
                    <span class="btn btn-app btn-lg btn-primary no-hover">
                        <span class="line-height-1 bigger-170 white icon">
                            <a href="{{ route('admin.doctor.edit', $doctor->id) }}">
                                <i class="ace-icon fa fa-pencil white"></i>
                            </a>
                        </span>
                        <br>
                        <span class="line-height-1 smaller-90">
                            <a class="white" href="{{ route('admin.doctor.edit', $doctor->id) }}">تعديل</a>
                        </span>
                    </span>
                    @if($doctor->status)
                        <span class="btn btn-app btn-lg btn-danger no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="{{ route('admin.doctor.status', [$doctor->id, 0]) }}">
                                    <i class="ace-icon fa fa-times white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.doctor.status', [$doctor->id, 0]) }}">إلغاء التفعيل</a>
                            </span>
                        </span>
                    @else
                        <span class="btn btn-app btn-lg btn-success no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="{{ route('admin.doctor.status', [$doctor->id, 1]) }}">
                                    <i class="ace-icon fa fa-check white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.doctor.status', [$doctor->id, 1]) }}">تفعيل</a>
                            </span>
                        </span>
                    @endif

                    @if(count($doctor->reservations) == 0)
                        <span class="btn btn-app btn-lg btn-danger no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="#" data-toggle="modal" data-target="#{{$doctor->id}}">
                                    <i class="ace-icon fa fa-close white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="#" data-toggle="modal"
                                   data-target="#{{$doctor->id}}">مسح</a>
                            </span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="profile-user-info profile-user-info-striped">
                <div class="profile-info-row">
                    <div class="profile-info-name">الإسم بالعربية</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->name_ar }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">الإسم بالإنجليزيه</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->name_en }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">المعلومات بالعربية</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->information_ar }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">المعلومات بالإنجليزية</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->information_en }}</span>
                    </div>
                </div>


                <div class="profile-info-row">
                    <div class="profile-info-name">النبذه بالعربية</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->abbreviation_ar }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">النبذة بالإنجليزية</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->abbreviation_en }}</span>
                    </div>
                </div>




                <div class="profile-info-row">
                    <div class="profile-info-name">النوع</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->gender == 2 ? "سيدة" : "رجل" }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">مقدم الخدمة</div>
                    <div class="profile-info-value">
                        <span class="editable"><a href="{{ route('admin.provider.view', $doctor->provider_id) }}">{{ $doctor->provider ? $doctor->provider->name_ar : "" }}</a></span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">التخصص</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->specification ? $doctor->specification->name_ar : "" }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">اللقب</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->nickname ? $doctor->nickname->name_ar : "" }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">الجنسية</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->nationality ? $doctor->nationality->name_ar : "" }}</span>
                    </div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-info-name">سعر الكشف</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->price }}</span>
                    </div>
                </div>


                <div class="profile-info-row">
                    <div class="profile-info-name">مده أنتظار الطبيب   </div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor-> waiting_period }}</span>
                    </div>
                </div>


                <div class="profile-info-row">
                    <div class="profile-info-name">الحالة</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $doctor->status == 1 ? "مفعّل" : "غير مفعّل" }}</span>
                    </div>
                </div>
            </div>
            <div class="space-12"></div>
        </div>
    </div>
@stop
