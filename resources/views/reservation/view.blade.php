@extends('layouts.master')

@section('title', 'عرض الحجز')

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
    {!! Breadcrumbs::render('view.reservation') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-image"></i> الحجز رقم {{ $reservation->reservation_no }}</h1>
        </div>
    </div>

    <div class="col-sm-12">
        <div id="user-profile-1" class="user-profile row">
            <div class="col-sm-12 right buttons">
                @if($reservation->approved == 0)
                    <span class="btn btn-app btn-lg btn-success no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="{{ route('admin.reservation.status', [$reservation->id, 1]) }}">
                                    <i class="ace-icon fa fa-check white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.reservation.status', [$reservation->id, 1]) }}">قبول</a>
                            </span>
                        </span>

                    <span class="btn btn-app btn-lg btn-danger no-hover">
                            <span class="line-height-1 bigger-170 white icon">
                                <a href="{{ route('admin.reservation.status', [$reservation->id, 2]) }}">
                                    <i class="ace-icon fa fa-times white"></i>
                                </a>
                            </span>
                            <br>
                            <span class="line-height-1 smaller-90">
                                <a class="white" href="{{ route('admin.reservation.status', [$reservation->id, 2]) }}">رفض</a>
                            </span>
                        </span>
                @endif


            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="profile-user-info profile-user-info-striped">
            <div class="profile-info-row">
                <div class="profile-info-name">رقم الحجز</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->reservation_no }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">تاريخ تقديم الحجز</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->created_at }}</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">تاريخ  الكشف </div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation-> day_date }} - ({{ $reservation->from_time}} -{{$reservation->to_time }})</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">اخر موعد قبل تحديث الحجز  </div>
                <div class="profile-info-value">
                    <span class="editable"> @if($reservation-> last_day_date){{ $reservation-> last_day_date }} - ({{ $reservation->last_from_time}} -{{$reservation->last_to_time }}) @else لم يتم تحديث موعد الحجز من قبل العميل حتي اللحظة   @endif</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">الحجز للمستخدم / للغير</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->people_id  !== null? "للغير" : "للمستخدم" }}</span>
                </div>
            </div>

            @if($reservation -> user_id)
                <div class="profile-info-row">
                    <div class="profile-info-name">اسم المستخدم</div>
                    <div class="profile-info-value">
                        <span class="editable"><a
                                href="{{ route('admin.user.view', $reservation->user_id) }}">{{ $reservation->user ? $reservation->user->name : "" }}</a></span>
                    </div>
                </div>
            @endif

            @if(isset($reservation->people_id))
                <div class="profile-info-row">
                    <div class="profile-info-name">المحجوز له</div>
                    <div class="profile-info-value">
                        <span class="editable">{{ $reservation->people->name }}</span>
                    </div>
                </div>
            @endif

            <div class="profile-info-row">
                <div class="profile-info-name">الطبيب</div>
                <div class="profile-info-value">
                    <span class="editable"><a
                            href="{{ route('admin.doctor.view', $reservation->doctor_id) }}">{{ $reservation->doctor ? $reservation->doctor->name_ar : "" }}</a></span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">مقدم الخدمة</div>
                <div class="profile-info-value">
                    <span class="editable"><a href="{{ route('admin.provider.view', $reservation->provider_id) }}">{{ $reservation->provider ? $reservation->provider->name_ar : "" }} - {{$reservation -> mainprovider}} </a></span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">طريقه الدفع</div>
                <div class="profile-info-value">
                    <span
                        class="editable">{{ $reservation->paymentMethod ? $reservation->paymentMethod->name_ar : "" }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">استخدم التأمين</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->use_insurance == 1 ? "نعم" : "لا" }}</span>
                </div>
            </div>


            @if($reservation -> use_insurance == 1)

                <div class="profile-info-row">
                    <div class="profile-info-name"> صوره التامين</div>
                    <div class="profile-info-value">
                        <span class="editable">  <img style="width: 100px; height: 100px;"
                                                      src="{{@$reservation -> user -> insurance_image}}"></span>
                    </div>
                </div>

            @endif



            @if($reservation -> use_insurance == 1)

                <div class="profile-info-row">
                    <div class="profile-info-name"> شركة التامين</div>
                    <div class="profile-info-value">
                        <span
                            class="editable{{$reservation -> user -> insurance_company_id}}">{{\App\Models\InsuranceCompany::where('id',$reservation -> user -> insurance_company_id) -> value('name_ar') }}</span>
                    </div>
                </div>

            @endif

            <div class="profile-info-row">
                <div class="profile-info-name">تقييم الطبيب</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->doctor_rate }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">تقييم مقدم الخدمة</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->provider_rate }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> كود الكوبون</div>
                <div class="profile-info-value">
                        <span class="editable">
                            @if(isset($reservation->promocode_id))
                                <a href="{{ route('admin.promoCode.view', $reservation->promocode_id) }}">{{ $reservation->promoCode->code }}</a>
                            @endif
                        </span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">قيمة الحجز</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->price }}</span>
                </div>
            </div>
            <div class="profile-info-row">
                <div class="profile-info-name">قيمة الفاتوره</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->bill_total }}</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">حالة الدفع</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $reservation->paid == 1 ? "مدفوع" : "غير مدفوع" }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">حالة الحجز</div>
                <div class="profile-info-value">
                        <span class="editable">

                            @if($reservation->approved == 1)
                                موافق عليه
                            @elseif($reservation->approved == 2 && $reservation-> rejection_reason !=0 && $reservation-> rejection_reason != null  && $reservation-> rejection_reason !=""  )
                                مرفوض بواسطة العياده
                            @elseif($reservation->approved == 3)
                                مكتمل بزياره العميل
                            @elseif($reservation->approved == 2 &&  ($reservation -> rejection_reason == null  or  $reservation -> rejection_reason == '' or  $reservation -> rejection_reason == 0) )
                                مكتمل بعدم زياره العميل
                            @elseif ($reservation->approved == 4)
                                $result = 'مشغول';
                            @elseif($reservation->approved == 5)
                                مرفوض بواسطة المستخدم
                                ({{$reservation-> user_rejection_reason}})
                            @else
                                معلق
                            @endif

                        </span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> تم الزياره</div>
                <div class="profile-info-value">
                        <span class="editable">

                            @if($reservation->approved == 3)
                                نعم
                            @elseif($reservation->approved == 2 &&  ($reservation -> rejection_reason == null  or  $reservation -> rejection_reason == '' or  $reservation -> rejection_reason == 0) )
                                لا
                            @else
                                --
                            @endif

                        </span>
                </div>
            </div>
        </div>
        <div class="space-12"></div>
    </div>
</div>
@stop
