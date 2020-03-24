@extends('layouts.master')

@section('title', 'الرئيسية')

@section('styles-after')
    <style>
        .shadowe {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        }
        body{

        }
    </style>
@stop
@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('dashboard') !!}
@stop
<div class="container-fluid">
    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-dashboard"></i> الرئيسية </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-12 infobox-container d-flex justify-content-center">
                <form class="d-flex flex-wrap" action="{{route('admin.search')}}" method="GET">
                    <div class="form-group has-float-label mx-2">
                        <input class="form-control " name="queryStr"
                               placeholder="ابحث باسم مقدم الخدمه, الفرع , المستخدم او الطبيب">
                    </div>
                    <div class="form-group has-float-label mx-2">
                        <select class="form-control" name="type_id">
                            <option value="doctor"> الاطباء</option>
                            <option value="provider"> مقدمي الخدمات</option>
                            <option value="branch">الافرع</option>
                            <option value="users">المستخدمين</option>
                        </select>
                    </div>
                    <div class="form-group has-float-label mx-2">
                        <button type="submit" class="btn btn-success form-control "><i class="fa fa-search"></i> ابحث
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-12 infobox-container">
                <div class="row">
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3 ">
                        <div class="infobox d-flex infobox-green shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-building"></i>
                            </div>
                            <div class="infobox-data">
                                <span class="infobox-data-number">{{ $activeProvidersCount }}</span>
                                <div class="infobox-content">مقدمى الخدمة المفعلين</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox d-flex infobox-blue shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-user-md"></i>
                            </div>
                            <div class="infobox-data">
                                <span class="infobox-data-number">{{ $activeDoctorsCount }}</span>
                                <div class="infobox-content">ألاطباء المفعلين</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox d-flex infobox-pink shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-users"></i>
                            </div>
                            <div class="infobox-data">
                                <span class="infobox-data-number">{{ $usersCount }}</span>
                                <div class="infobox-content">المستخدمين</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-orange2 shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon  fa fa-hourglass-start"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.reservation') }}?status=pending">
                                    <span class="infobox-data-number">{{ $pendingReservations }}</span>
                                    <div class="infobox-content">الحجوزات المعلقة</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-blue2 shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-check"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.reservation') }}?status=approved">
                                    <span class="infobox-data-number">{{ $approvedReservations }}</span>
                                    <div class="infobox-content">الحجوزات الموافق عليها</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-close"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.reservation') }}?status=reject">
                                    <span class="infobox-data-number">{{ $refusedReservationsByProvider }}</span>
                                    <div class="infobox-content">الحجوزات المرفوضه من مقدمي الخدمات</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-close"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.reservation') }}?status=rejected_by_user">
                                    <span class="infobox-data-number">{{ $refusedReservationsByUser }}</span>
                                    <div class="infobox-content">الحجوزات المرفوضه من المستخدمين</div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-clock-o"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;" href="{{ route('admin.reservation') }}?status=delay">
                                    <span
                                        class="infobox-data-number font-weight-bold h3">{{ $reservation_notReplayForMore15Mins  }}</span>
                                    <div class="infobox-content">الحجوزات المتاخره الرد لـ 15 دقيقة</div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-blue2 shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-check"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.reservation') }}?status=complete_visited">
                                    <span class="infobox-data-number font-weight-bold h3"
                                          style="color: #3983c2;">{{ $completedReservationsWithVisited  }}</span>
                                    <div class="infobox-content">الحجوزات المكتملة بزياره العميل</div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-close"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.reservation') }}?status=complete_not_visited">
                                    <span
                                        class="infobox-data-number font-weight-bold h3">{{ $completedReservationsWithNotVisited  }}</span>
                                    <div class="infobox-content">الحجوزات المكتملة بدون زياره العميل</div>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{--    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                            <div class="infobox infobox-red">

                                <div class="infobox-icon">
                                    <i class="ace-icon fa fa-ticket"></i>
                                </div>
                                <div class="infobox-data">
                                    <a style="text-decoration: none;" href="{{ route('admin.reservation') }}?status=today_tomorrow">
                                        <span class="infobox-data-number font-weight-bold h3">{{ $todayAndTomorrowReservationCount }}</span>
                                        <div class="infobox-content"> حجوزات اليوم والغد</div>
                                    </a>
                                </div>

                            </div>

                        </div>

    --}}
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox d-flex infobox-green shadowe">

                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-plus"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.reservation') }}">
                                    <span class="infobox-data-number font-weight-bold h3"
                                          style="color: #9abc32;">{{ \App\Models\Reservation::count() }}</span>
                                    <div class="infobox-content">مجموع الحجوزات</div>
                                </a>
                            </div>

                        </div>

                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-users"></i>
                            </div>
                            <div class="infobox-data">
                                <span class="infobox-data-number font-weight-bold h3">%{{  @$percentage }} </span>
                                <div class="infobox-content">نسبة العملاء الذين قاموا بالحجز من مجموع الأعضاء المسجلين
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-users"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.user') }}?status=no_reservations">
                                <span
                                    class="infobox-data-number font-weight-bold h3">{{  @$totalUserNotHasReservastiond }} </span>
                                    <div class="infobox-content">مجموع من قام بالتسجيل ولم يحجز</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-mobile"></i>
                            </div>
                            <div class="infobox-data">
                                <span
                                    class="infobox-data-number font-weight-bold h3">{{@$TotalUserDownloadAppAndRegister}}</span>
                                <div class="infobox-content">المجموع الكلي لتحميلات التطبيق للاعضاء المسجلين</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-android"></i>
                            </div>
                            <div class="infobox-data">
                                <span
                                    class="infobox-data-number font-weight-bold h3">{{@$totalUserRegisterUsingAndroidApp}}</span>
                                <div class="infobox-content">المجموع الكلي لتحميلات الاندرويد للاعضاء المسجلين</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-red shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-apple"></i>
                            </div>
                            <div class="infobox-data">
                                <span
                                    class="infobox-data-number font-weight-bold h3">{{@$totalUserRegisterUsingIOSApp}}</span>
                                <div class="infobox-content">المجموع الكلي لتحميلات IOS للاعضاء المسجلين</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 my-3">
                        <div class="infobox infobox-green2 shadowe">
                            <div class="infobox-icon">
                                <i class="ace-icon fa fa-gift"></i>
                            </div>
                            <div class="infobox-data">
                                <a style="text-decoration: none;"
                                   href="{{ route('admin.promoCode.mostreserved') }}">
                                <span
                                    class="infobox-data-number font-weight-bold h3">{{@$mostOffersBooking -> count()}}</span>
                                    <div class="infobox-content">اكثر العروض حجزا</div>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <br><br>
        <div class="row ">
            <div class="col-md-12">
                <div class="page-header">
                    <h1><i class="menu-icon fa fa-bar-chart"></i> الاحصائيات </h1>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 my-3">
                <div class="infobox infobox-green p-4 shadowe">
                    <div class="infobox-icon">
                        <i class="ace-icon fa fa-calendar"></i>
                    </div>
                    <div class="infobox-data">
                        <div class="infobox-content mb-3"> أكثر أيام الأسبوع حجزا</div>
                        @if(isset($mostBookingDay) && $mostBookingDay -> count() > 0)
                            @forelse($mostBookingDay as $day)
                                {{__('messages.'.\App\Traits\Dashboard\ReservationTrait::getdayNameByDate($day['day_date']))}}
                            @empty
                                'لايوجد بيانات  '
                            @endforelse
                        @endif

                    </div>
                </div>
            </div>
            <div class="col-12 my-3">
                <div class="infobox infobox-red p-4 shadowe">
                    <div class="infobox-icon">
                        <i class="ace-icon fa fa-history"></i>
                    </div>
                    <div class="infobox-data">
                        <div class="infobox-content mb-3">أكثر الاختصاصات حجزا</div>
                        @if(isset($mostBookingSpecifications) && $mostBookingSpecifications -> count() > 0)
                            @forelse($mostBookingSpecifications as $specification)
                                {{\App\Traits\Dashboard\SpecificationTrait::getSpecificationNameById($specification['specification_id'])}}
                            @empty
                                'لايوجد بيانات '
                            @endforelse
                        @endif

                    </div>
                </div>
            </div>

            <div class="col-12 my-3">
                <div class="infobox infobox-pink p-4 shadowe">
                    <div class="infobox-icon">
                        <i class="ace-icon fa fa-building"></i>
                    </div>
                    <div class="infobox-data">
                        <div class="infobox-content mb-3">أكثر مقدمين الخدمة حجوزات</div>
                        @if(isset($mostBookingProviders) && $mostBookingProviders -> count() > 0)
                            @forelse($mostBookingProviders as $provider)
                                {{ \App\Traits\Dashboard\ProviderTrait::getProviderNameById($provider['provider_id'])  }}
                                @if(!$provider->last)
                                    -
                                @endif
                            @empty
                                'لايوجد بيانات '
                            @endforelse
                        @endif

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 my-3">
                <div class="infobox infobox-blue2 p-4 shadowe">
                    <div class="infobox-icon">
                        <i class="ace-icon fa fa-user-md"></i>
                    </div>
                    <div class="infobox-data">
                        <div class="infobox-content mb-3">أكثر الأطباء حجزا</div>
                        @if(isset($mostBookingDoctor) && $mostBookingDoctor -> count() > 0)
                            @forelse($mostBookingDoctor as $doctor)
                                {{ \App\Traits\Dashboard\DoctorTrait::getDoctorNameById($doctor['doctor_id']) }} -
                            @empty
                                'لايوجد بيانات '
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 my-3">
                <div class="infobox infobox-blue p-4 shadowe">
                    <div class="infobox-icon">
                        <i class="ace-icon fa fa-clock-o"></i>
                    </div>
                    <div class="infobox-data">
                        <div class="infobox-content mb-3"> أكثر ساعات الأسبوع حجزا</div>
                        @if(isset($mostBookingHour) && $mostBookingHour -> count() > 0)
                            @forelse($mostBookingHour as $hour)
                                {{  $hour['from_time']  .' - '.  $hour['to_time']  }}
                            @empty
                                'لايوجد بيانات '
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>


@stop

