@extends('layouts.master')

@section('title', 'تفاصيل العرض')

@section('styles')
    <style>
        .profile-picture {
            border: 0 !important;
            box-shadow: none !important;
        }

        .icon {
            margin-left: 5px;
        }
    </style>
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('view.offers') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-image"></i> {{ $offer-> title_ar }}</h1>
        </div>
    </div>

    <div class="col-sm-12">
        <div id="user-profile-1" class="user-profile row">

            <div class="col-xs-12 col-sm-3 center">
                <div>
                    <div class="profile-picture">
                        <img id="avatar" class="editable img-responsive" alt="Icon URL"
                             src="{{ asset($offer->photo ? $offer->photo : 'images/no_image.png') }}"/>
                    </div>
                </div>
                <div class="space-10"></div>
            </div>

            <div class="col-sm-12 center buttons">
                <span class="btn btn-app btn-lg btn-primary no-hover">
                    <span class="line-height-1 bigger-170 white icon">
                        <a href="{{ route('admin.offers.edit', $offer->id) }}">
                            <i class="ace-icon fa fa-pencil white"></i>
                        </a>
                    </span>
                    <br>
                    <span class="line-height-1 smaller-90">
                        <a class="white" href="{{ route('admin.offers.edit', $offer->id) }}">تعديل</a>
                    </span>
                </span>

                <span class="btn btn-app btn-lg btn-danger no-hover">
                    <span class="line-height-1 bigger-170 white icon">
                        <a href="#" data-toggle="modal" data-target="#{{$offer->id}}">
                            <i class="ace-icon fa fa-close white"></i>
                        </a>
                    </span>
                    <br>
                    <span class="line-height-1 smaller-90">
                        <a class="white" href="#" data-toggle="modal"
                           data-target="#{{$offer->id}}">مسح</a>
                    </span>
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="profile-user-info profile-user-info-striped">
            <div class="profile-info-row">
                <div class="profile-info-name">الرمز</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->code }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> السعر قبل الخصم</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer-> price }} </span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> السعر بعد الخصم</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->price_after_discount }}</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">الخصم</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->discount }}%</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">العدد المتاح</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->available_count }}</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">نوع العدد المتاح</div>
                <div class="profile-info-value">
                    <span
                        class="editable">{{ $offer->available_count_type == 'more_than_once' ? 'اكثر من مرة' : 'مرة واحدة' }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">تاريخ البداية</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->started_at }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">تاريخ الإنتهاء</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->expired_at }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الحالة</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->status ? "مفعل" : "غير مفعّل" }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">مقدم الخدمة</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->provider ? $offer->provider->name_ar : "" }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الجنس</div>
                <div class="profile-info-value">
                    @if ($offer->gender == 'males')
                        <span class="editable">رجال فقط</span>
                    @elseif ($offer->gender == 'females')
                        <span class="editable">نساء فقط</span>
                    @else
                        <span class="editable">رجال ونساء</span>
                    @endif
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">نوع الجهاز</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $offer->device_type }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الافرع</div>
                <div class="profile-info-value">
                    <ul>
                        @foreach($offer['offerBranches'] as $key => $branch)
                            <li>
                                <a href="{{ route('admin.branch.view', $branch->branch_id) }}">{{ getBranchById($branch->branch_id) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{--<div class="profile-info-row">
                <div class="profile-info-name">الاطباء</div>
                <div class="profile-info-value">
                    <ul>
                        @foreach($offer['promocodedoctors'] as $key => $doctor)
                            <li>
                                <a href="{{ route('admin.doctor.view', $doctor-> doctor_id) }}">{{ getDoctorById($doctor-> doctor_id) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>--}}


            <div class="profile-info-row">
                <div class="profile-info-name">الحجوزات المرتبطة بالرمز</div>
                <div class="profile-info-value">
                    <span class="editable">
                        <ul>
                            @foreach($offer->reservations as $key => $reservation)
                                <li><a href="{{ route('admin.reservation.view', $reservation->id) }}">{{ $reservation->reservation_no }}</a></li>
                            @endforeach
                        </ul>
                    </span>
                </div>
            </div>
        </div>
        <div class="space-12"></div>

    </div>

    <div class="col-md-6">
        <label># المحتوى بالعربية #</label>
        <ul>
            @foreach($offer['contents'] as $key => $content)
                <li>{{ $content->content_ar }}</li>
            @endforeach
        </ul>
    </div>
    <div class="col-md-6">
        <label># المحتوى بالإنجليزية #</label>
        <ul>
            @foreach($offer['contents'] as $key => $content)
                <li>{{ $content->content_en }}</li>
            @endforeach
        </ul>
    </div>

    <div class="col-md-12">
        <label># طرق الدفع #</label>
        <ul>
            @foreach($offer['paymentMethods'] as $key => $payment)
                @if ($payment->id == 6)
                    <li>
                        <span>{{ $payment->name_ar }} </span>
                        @if ($payment->pivot->payment_amount_type == 'custom')
                            <span> # نوع المبلغ : مبلغ معين</span>
                            <span> # المبلغ : {{ $payment->pivot->payment_amount }}</span>
                        @else
                            <span> # نوع المبلغ : المبلغ كامل</span>
                        @endif

                    </li>
                @else
                    <li>{{ $payment->name_ar }}</li>
                @endif
            @endforeach
        </ul>
    </div>

    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon"></i># توقيتات الأفرع </h1>
        </div>
    </div>
    <div class="col-md-12">

        @foreach($offerBranchTimes as $key => $branch)
            <h3># الفرع : {{ $branch['branch_name'] }}</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>اليوم</th>
                        <th>من</th>
                        <th>الى</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($branch['days'] as $k => $day)
                        <tr>
                            @if ($day['day_code'] == "sat")
                                <td>السبت</td>
                            @elseif ($day['day_code'] == "sun")
                                <td>الأحد</td>
                            @elseif ($day['day_code'] == "mon")
                                <td>الاثنين</td>
                            @elseif ($day['day_code'] == "tue")
                                <td>الثلاثاء</td>
                            @elseif ($day['day_code'] == "wed")
                                <td>الأربعاء</td>
                            @elseif ($day['day_code'] == "thu")
                                <td>الخميس</td>
                            @else
                                <td>الجمعة</td>
                            @endif
                            <td>{{ $day['start_from'] }}</td>
                            <td>{{ $day['end_to'] }}
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
        @endforeach

    </div>

    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon"></i># أقسام العرض </h1>
        </div>
    </div>
    <div class="col-md-12">

        @foreach($childCats as $key => $child)
            <h3># القسم الرئيسى : {{ $child->parentCategory['name_ar'] }}</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>القسم الفرعى</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{ $child->name_ar }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endforeach

    </div>

    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-user"></i> المستفادين من الكوبون </h1>
        </div>
    </div>
    <div class="col-md-12">

        <table id="example" class="table table-striped table-bordered" style="width:100%">
            <thead>
            <tr>
                <th>الاسم</th>
                <th>رقم الهاتف</th>

            </tr>
            </thead>
            <tbody>
            @if(isset($beneficiaries) && $beneficiaries -> count() > 0 )
                @foreach($beneficiaries as $user)
                    <tr>
                        <td>{{$user -> name}} </td>
                        <td>{{$user -> mobile}}</td>

                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>

</div>
@stop
@section('scripts')
    <script>
        $(document).ready(function () {
            $('#example').DataTable();
        });
    </script>
@stop
