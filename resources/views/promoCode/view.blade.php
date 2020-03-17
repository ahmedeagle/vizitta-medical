@extends('layouts.master')

@section('title', 'عرض رمز الخصم')

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
    {!! Breadcrumbs::render('view.promoCode') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-image"></i> {{ $promoCode-> title_ar }}</h1>
        </div>
    </div>

    <div class="col-sm-12">
        <div id="user-profile-1" class="user-profile row">

            <div class="col-xs-12 col-sm-3 center">
                <div>
                    <div class="profile-picture">
                        <img id="avatar" class="editable img-responsive" alt="Icon URL"
                             src="{{ asset($promoCode->photo ? $promoCode->photo : 'images/no_image.png') }}"/>
                    </div>
                </div>
                <div class="space-10"></div>
            </div>

            <div class="col-sm-12 center buttons">
                <span class="btn btn-app btn-lg btn-primary no-hover">
                    <span class="line-height-1 bigger-170 white icon">
                        <a href="{{ route('admin.promoCode.edit', $promoCode->id) }}">
                            <i class="ace-icon fa fa-pencil white"></i>
                        </a>
                    </span>
                    <br>
                    <span class="line-height-1 smaller-90">
                        <a class="white" href="{{ route('admin.promoCode.edit', $promoCode->id) }}">تعديل</a>
                    </span>
                </span>

                <span class="btn btn-app btn-lg btn-danger no-hover">
                    <span class="line-height-1 bigger-170 white icon">
                        <a href="#" data-toggle="modal" data-target="#{{$promoCode->id}}">
                            <i class="ace-icon fa fa-close white"></i>
                        </a>
                    </span>
                    <br>
                    <span class="line-height-1 smaller-90">
                        <a class="white" href="#" data-toggle="modal"
                           data-target="#{{$promoCode->id}}">مسح</a>
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
                    <span class="editable">{{ $promoCode->code }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> السعر قبل الخصم</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $promoCode-> price }} </span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">  السعر بعد الخصم </div>
                <div class="profile-info-value">
                    <span class="editable">{{ $promoCode->price_after_discount }}</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">الخصم</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $promoCode->discount }}%</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">العدد المتاح</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $promoCode->available_count }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الحالة</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $promoCode->status ? "مفعل" : "غير مفعّل" }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">تاريخ الإنتهاء</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $promoCode->expired_at }}</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">مقدم الخدمة</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $promoCode->provider ? $promoCode->provider->name_ar : "" }}</span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">الافرع</div>
                <div class="profile-info-value">
                    <ul>
                        @foreach($promoCode['promocodebranches'] as $key => $branch)
                            <li>
                                <a href="{{ route('admin.branch.view', $branch->branch_id) }}">{{ getBranchById($branch->branch_id) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">الاطباء</div>
                <div class="profile-info-value">
                    <ul>
                        @foreach($promoCode['promocodedoctors'] as $key => $doctor)
                            <li>
                                <a href="{{ route('admin.doctor.view', $doctor-> doctor_id) }}">{{ getDoctorById($doctor-> doctor_id) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name">الحجوزات المرتبطة بالرمز</div>
                <div class="profile-info-value">
                    <span class="editable">
                        <ul>
                            @foreach($promoCode->reservations as $key => $reservation)
                                <li><a href="{{ route('admin.reservation.view', $reservation->id) }}">{{ $reservation->reservation_no }}</a></li>
                            @endforeach
                        </ul>
                    </span>
                </div>
            </div>
        </div>
        <div class="space-12"></div>


    </div>
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-user"></i>  المستفادين من الكوبون </h1>
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
