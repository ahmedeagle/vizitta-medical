@extends('layouts.master')

@section('title', 'الحجوزات')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('reservation') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-ticket"></i>الحجوزات </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-12  d-flex flex-wrap justify-content-center">
            <div class="m-2">
                <a class="btn   btn-lg btn-bold" href="{{route('admin.reservation')}}?status=all">
                    الكل
                </a>
            </div>
            <div class="m-2">
                <a class="btn  btn-white btn-primary btn-lg btn-bold"
                   href="{{route('admin.reservation')}}?status=pending">
                    المعلقة
                </a>
            </div>
            <div class="m-2">
                <a class="btn btn-white btn-primary btn-lg btn-bold"
                   href="{{route('admin.reservation')}}?status=approved">
                    موافق عليها
                </a>
            </div>
            <div class="m-2">
                <a class="btn btn-white btn-info btn-lg btn-bold" href="{{route('admin.reservation')}}?status=reject">
                  مرفوضه من العياده
                </a>
            </div>
            <div class="m-2">
                <a class="btn btn-white btn-info btn-lg btn-bold" href="{{route('admin.reservation')}}?status=rejected_by_user">
                  مرفوضه من المستخدم
                </a>
            </div>
            <div class="m-2">
                <a class="btn btn-white btn-success btn-lg btn-bold"
                   href="{{route('admin.reservation')}}?status=complete_visited">
                    مكتمله بالزياره
                </a>
            </div>
            <div class="m-2">
                <a class="btn btn-white btn-success btn-lg btn-bold"
                   href="{{route('admin.reservation')}}?status=complete_not_visited">
                    مكتمله بدون زياره
                </a>
            </div>

            <div class="m-2">
                <a class="btn btn-white btn-warning btn-lg btn-bold" href="{{route('admin.reservation')}}?status=delay">
                    متاخره الرد
                </a>
            </div>
        </div>
    </div>
    <br><br>
    <div class="row">
        <div class="col-12  d-flex flex-wrap justify-content-center">
            <form class="d-flex flex-wrap" action="{{route('admin.reservation')}}" method="GET">
                <div class="form-group has-float-label mx-2">
                    <input class="form-control " name="generalQueryStr" value="{{@Request::get('generalQueryStr')}}"
                           placeholder="ابحث باحدي الحقول ">
                </div>
                <div class="form-group has-float-label mx-2" style="display: none;">
                    <button type="submit" class="btn btn-success form-control "><i class="fa fa-search"></i> ابحث
                    </button>
                </div>
            </form>
        </div>
    </div>
    <br>

    <div class="col-md-12">
        <table id="reservation-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>رقم الحجز</th>
                <th>يوم الحجز</th>
                <th>من</th>
                <th>الي</th>
                <th>المستخدم</th>
                <th>الطبيب</th>
                <th> مقدم الخدمة</th>
                <th> الفرع</th>
                <th>طريقه الدفع</th>
                <th>الموافقة</th>
                <th> قيمه الكشف</th>
                <th>قيمة الفاتورة</th>
                <th> نوع النسبة</th>
                <th>العمليات</th>
            </tr>
            </thead>

            <tbody>
            @if(isset($reservations) &&  $reservations -> count() > 0)
                @foreach($reservations as $reservation)
                    <tr>
                        <td>{{$reservation -> reservation_no}}</td>
                        <td>{{$reservation -> day_date}}</td>
                        <td>{{$reservation -> from_time}}</td>
                        <td>{{$reservation -> to_time}}</td>
                        <td>{{$reservation -> user -> name}}</td>
                        <td>{{$reservation -> doctor -> name_ar}}</td>
                        <td>{{$reservation -> mainprovider}}</td>
                        <td>{{$reservation -> provider -> name_ar}}</td>
                        <td>{{$reservation -> paymentMethod -> name_ar}}</td>
                        <td>{{$reservation -> getApproved()}}</td>
                        <td>{{$reservation -> price}}</td>
                        <td>{{$reservation -> bill_total}}</td>
                        <td>{{$reservation -> discount_type}}</td>
                        <td>
                            <div class="actions">
                                <div class="col-md-2">
                                    <a class="btn btn-white btn-primary btn-lg"
                                       href="{{ route('admin.reservation.view', $reservation->id) }}" title="التفاصيل">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </div>

                                @if($reservation->approved == 0)
                                    <div class="col-md-2">
                                        <a class="btn btn-white btn-success btn-lg"
                                           href="{{ route('admin.reservation.status', [$reservation->id, 1]) }}"
                                           title="قبول">
                                            <i class="fa fa-check"></i>
                                        </a>
                                    </div>
                                    <div class="col-md-2">
                                        <a data_reser_id="{{$reservation->id}}" data_reser_status="2"
                                           class="btn btn-white btn-warning btn-lg reject_reason_btn"
                                           href="{{ route('admin.reservation.status', [$reservation->id, 2]) }}"
                                           title="رفض">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    </div>
                                @endif

                                @if($reservation->approved != 1)
                                    <div class="col-md-2">
                                        <a class="btn btn-white btn-danger btn-lg"
                                           href="{{ route('admin.reservation.delete', $reservation->id) }}" title="مسح">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                @endif

                                @if($reservation->approved  == 0 or  $reservation->approved == 1)
                                    <div class="col-md-2">
                                        <a class="btn btn-white btn-danger btn-lg"
                                           href="{{ route('admin.reservation.update', $reservation->id) }}"
                                           title=" تعديل موعد ">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </div>
                                @endif

                            </div>
                        </td>
                    </tr>

                @endforeach
            @endif


            </tbody>

        </table>
        {!! $reservations ->appends(request()->input())->links('pagination.default') !!}
    </div>
</div>


<!-- Modal -->
<div class="modal fade in" id="rejectionModal" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">رفض الحجز </h4>
            </div>
            <form action="{{Route('admin.reservation.rejectiond')}}" method="GET">
                <div class="modal-body">
                    <select class="form-control" name="rejection_reason">
                        @if(isset($reasons) && $reasons -> count() > 0)
                            @foreach($reasons as $reason)
                                <option value="{{ $reason -> id  }}">{{$reason -> name_ar}}</option>
                            @endforeach
                        @endif
                    </select>
                    <input type="hidden" class="form-control" name="id" id="reservation_id" value=""
                           placeholder=" سبب رفض الحجز "/>
                    <input type="hidden" class="form-control" name="status" id="status_id" value="2"
                           placeholder=" سبب رفض الحجز "/>
                </div>
                <div class="modal-footer">
                    <button type="submit" style="margin-left: 10px;" onclick=""
                            class="add-btn-list btn btn-danger confirmRejection"> تأكيد الرفض
                    </button>
                    <button type="button" style="margin-left: 10px;" onclick=""
                            class="add-btn-list btn btn-success " data-dismiss="modal"> تراجع
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('scripts')
    <script>

        let allowChek = true;
        $(document).on('click', '.reject_reason_btn', function (e) {
            e.preventDefault();
            $('#reservation_id').val($(this).attr('data_reser_id'));
            $('#rejectionModal').modal('toggle');
        });

        $(document).ready(function () {
            $('#reservation-table').DataTable(
                {
                    "paging": false,
                    "bInfo": false,
                    "searching": false,
                    "ordering": false
                }
            );
        });

    </script>
@stop
