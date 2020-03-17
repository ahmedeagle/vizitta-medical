@extends('layouts.master')

@section('title', 'عرض بيانات التطبيق')

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
    {!! Breadcrumbs::render('information') !!}
@stop

<div class="page-content">

    <div class="col-md-12">
        <h4 class="widget-title smaller"><i class="ace-icon fa fa-credit-card"></i> تفاصيل الرصيد </h4>
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم</th>
                <th>الرصيد </th>
                <th>العمليات</th>
            </tr>
            </thead>
        </table>
    </div>


    <div class="col-md-12">
        <h4 class="widget-title smaller"><i class="ace-icon fa fa-credit-card"></i> تفاصيل الرصيد للكوبونات المدفوعة </h4>
        <table id="coupon-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th> #  </th>
                <th> اسم  المستخدم   </th>
                <th> الهاتف   </th>
                <th> اسم العرض  </th>
                <th>   توقيت الدفع   </th>
                <th>  قيمه الاداره من الكوبون      </th>
                <th>  القيمة المدفوعة   </th>
             </tr>
            </thead>
        </table>
    </div>


</div>
@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#dynamic-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.data.providers') }}",
                columns: [
                    {name: 'name_ar'},
                    {name:  'adminprices' , orderable: false, searchable: false},
                    {name: 'admin_action', orderable: false, searchable: false},
                ],
            });

            $('#coupon-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.data.coupon.balance') }}",
                columns: [
                    {name: 'id'},

                    {name: 'user.name', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    }, {name: 'user.mobile', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'offer.title_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'created_at'},
                    {name:'provider_value_of_coupon'
                    },
                    {name: 'amount'},


                ],
            });

           /* $('#coupon-table  tr td').each( function ()
            {
                    $(this).html( '<input type="date" name="created_at" placeholder="搜索" value=" " style="width:100%"/>' );

            });*/

        });
    </script>
@stop
