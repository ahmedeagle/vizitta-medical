@extends('layouts.master')

@section('title', 'الحجوزات')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('providers.reservation') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-ticket"></i> سجل حجوزات - {{$provider -> name_ar}} </h1>
        </div>
    </div>

    <div class="col-md-12  page-header">
        <div class="col-md-5">
            <h1><i class="menu-icon fa fa-ticket"></i>عدد الحجوزات المكتملة : {{@$count}}   </h1>
        </div>
        <div class="col-md-5">
            <h1><i class="menu-icon fa fa-money"></i> قيمه الحجوزات المكتملة : {{@$total }}       </h1>
        </div>

        <div class="col-md-2">
            <h1 title="فلتره النتائج ">
                <a href="" class="btn btn-default btn-rounded mb-4" data-toggle="modal" data-target="#modalFilterForm">
                    <i class="menu-icon fa fa-filter"></i></a>
            </h1>
        </div>
    </div>


    <br><br>


    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>رقم الحجز</th>
                <th>اليوم</th>
                <th>من</th>
                <th>إلى</th>
                <th>المستخدم</th>
                <th>الطبيب</th>
                <th> مقدم الخدمة</th>
                <th> الفرع</th>
                <th>طريقه الدفع</th>
                <th>الموافقة</th>
                <th> قيمه الكشف</th>
                <th>قيمة الفاتورة</th>
                <th> نوع النسبة</th>
                <th> قيمه نسبة التطبيق</th>
                <th>العمليات</th>
            </tr>
            </thead>
        </table>
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
                    <input type="text" class="form-control" name="rejection_reason" placeholder=" سبب رفض الحجز "/>
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


@include('includes.filter',['provider_id' =>  $provider -> id, 'provider_name' => $provider -> name_ar])

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
            $('#dynamic-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: " {!!  route('admin.provider.reservations.data',['provider_id' => $provider -> id,'from_date' => $from_date,'to_date' => $to_date,'doctor_id' => $doctor_id,'branch_id' => $branch_id,'payment_method_id' => $payment_method_id]) !!}",
                columns: [
                    {name: 'reservation_no'},
                    {name: 'day_date'},
                    {name: 'from_time'},
                    {name: 'to_time'},
                    {
                        name: 'user.name', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {
                        name: 'doctor.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {
                        name: 'mainprovider'
                    },
                    {
                        name: 'provider.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {
                        name: 'paymentMethod.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'approved'},
                    {name: 'price'},
                    {name: 'bill_total'},
                    {name: "discount_type"},
                    {name: "app_income"},
                    {name: 'action', orderable: false, searchable: false}
                ],
            });
        });
    </script>
@stop
