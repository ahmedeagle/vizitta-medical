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
    {!! Breadcrumbs::render('view.branch') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-image"></i> {{ $branch->name_ar }}</h1>
        </div>
    </div>

    <div class="col-sm-12">
        <div id="user-profile-1" class="user-profile row">
            <div class="col-xs-12 col-sm-3 center">
                <div>
                    <div class="profile-picture">
                        <img id="avatar" class="editable img-responsive" alt="Icon URL"
                             src="{{ asset($branch->logo ? $branch->logo : 'images/no_image.png') }}"/>
                    </div>
                </div>
                <div class="space-10"></div>
            </div>

            <div class="col-sm-9 center">

                  <span class="btn btn-app btn-lg btn-primary no-hover">
                        <span class="line-height-1 bigger-170 white icon">
                            <a href="{{ route('admin.branch.edit', $branch->id) }}">
                                <i class="ace-icon fa fa-user-md white"></i>
                            </a>
                        </span>
                        <br>
                        <span class="line-height-1 smaller-90">
                            <a class="white" href="{{ route('admin.doctor.add',['branch_id' => $branch->id]) }}">أضافة طبيب </a>
                        </span>
                    </span>

                <span class="btn btn-app btn-lg btn-primary no-hover">
                        <span class="line-height-1 bigger-170 white icon">
                            <a href="{{ route('admin.branch.edit', $branch->id) }}">
                                <i class="ace-icon fa fa-pencil white"></i>
                            </a>
                        </span>
                        <br>
                        <span class="line-height-1 smaller-90">
                            <a class="white" href="{{ route('admin.branch.edit', $branch->id) }}">تعديل</a>
                        </span>
                    </span>
                @if($branch->status)
                    <span class="btn btn-app btn-lg btn-danger no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="{{ route('admin.branch.status', [$branch->id, 0]) }}">
                                    <i class="ace-icon fa fa-times white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.branch.status', [$branch->id, 0]) }}">إلغاء التفعيل</a>
                            </span>
                        </span>
                @else
                    <span class="btn btn-app btn-lg btn-success no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="{{ route('admin.branch.status', [$branch->id, 1]) }}">
                                    <i class="ace-icon fa fa-check white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.branch.status', [$branch->id, 1]) }}">تفعيل</a>
                            </span>
                        </span>
                @endif

                <span class="btn btn-app btn-lg btn-danger no-hover">
                        <span class="line-height-1 bigger-170 white icon">
                            <a href="#" data-toggle="modal" data-target="#{{$branch->id}}">
                                <i class="ace-icon fa fa-close white"></i>
                            </a>
                        </span>
                        <br>
                        <span class="line-height-1 smaller-90">
                            <a class="white" href="#" data-toggle="modal"
                               data-target="#{{$branch->id}}">مسح</a>
                        </span>
                    </span>
            </div>
        </div>
    </div>


    <div class="col-md-12  page-header">
        <div class="col-md-6">
            <h1><i class="menu-icon fa fa-ticket"></i> معدل قبول الحجوزات : {{@$acceptance_rate}}   </h1>
        </div>
        <div class="col-md-6">
            <h1><i class="menu-icon fa fa-money"></i> معدل رفض الحجوزات : {{@$refusal_rate }}         </h1>
        </div>


    </div>

    <div class="col-md-12  page-header">
        <div class="col-md-4">
            <p style="font-size: 14px;"><span>  الحجوزات المقبوله (الموافقة والمكتمله بالحضور) </span>
                :<span style="font-size: 20px;" class="text-danger"> {{@$acceptanceReservationCount}} </span>  </p>
        </div>

        <div class="col-md-4">
            <p style="font-size: 14px;"><span>   الحجوزات المرفوضه من قبل العيادة </span>
                : <span style="font-size: 20px;" class="text-danger">{{@$refusedReservationCount}}</span>   </p>
         </div>

        <div class="col-md-4">
            <p style="font-size: 14px;"><span>   جميع الحجوزات </span>
                : <span style="font-size: 20px;" class="text-danger"> {{@$allReservationCount}} </span>  </p>
         </div>


    </div>


    <div class="col-md-12">
        <div class="profile-user-info profile-user-info-striped">
            <div class="profile-info-row">
                <div class="profile-info-name">الإسم بالعربية</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->name_ar }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الإسم بالإنجليزيه</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->name_en }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">النوع</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->type ? $branch->type->name_ar : "-" }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">البريد الإلكترونى</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->email }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">العنوان</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->address }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">رقم الجوال</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->mobile }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الرقم التجارى</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->commercial_no }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">Latitude</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->latitude }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">Longitude</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->longitude }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">المدينة</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->city ? $branch->city->name_ar : "" }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الحى</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->district ? $branch->city->name_ar : "" }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الشارع</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->street }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">التقييم</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->rate }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الرصيد</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $branch->balance }}</span>
                </div>
            </div>


            <div class="profile-info-row full">
                <div class="profile-info-name"> الاطباء</div>
                <div class="profile-info-value">
                        <span class="editable">
                            <ul class="floating">
                                @foreach($branch->doctors as $doctor)
                                    <li><a href="{{ route('admin.doctor.view', $doctor->id) }}">{{ $doctor->name_ar }}</a></li>
                                @endforeach
                            </ul>
                        </span>
                </div>
            </div>
        </div>
        <div class="space-12"></div>
    </div>
</div>
@stop
