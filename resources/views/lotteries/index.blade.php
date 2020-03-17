@extends('layouts.master')

@section('title', 'عيادات السحب العشوائي ')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('lotteries') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-gift"></i> عيادات السحب العشوائي</h1>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم بالعربيه</th>
                <th>الإسم بالإنجليزيه</th>
                <th> اسم المستخدم</th>
                <th>الجوال</th>

                <th>مقدم الخدمة التابع له</th>
                <th>الحالة</th>
                <th>تاريخ الإنضمام</th>
                <th>العمليات</th>
            </tr>
            </thead>
        </table>
    </div>
</div>


<!-- add gift to lottey branch   -->
<div class="modal fade in" id="gift_Modal" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><span id="providerName"></span></h4>
            </div>
            <form action="{{Route('admin.lotteriesBranches.addGiftToBranch')}}" method="Post">
                {{csrf_field()}}
                <div class="modal-body">
                    <input type="text" name="title"
                           placeholder="ادخل اسم الهدية " class="form-control"/>
                    <br>
                    <input onkeypress="return event.charCode >= 48" type="number" min="1" class="form-control"
                           name="amount"
                           placeholder="ادخل العدد المتاح "/>


                    <input type="hidden" class="form-control" name="provider_id" id="providerId" value=""
                           placeholder="  "/>

                </div>
                <div class="modal-footer">
                    <button type="button" style="margin-left: 10px;"
                            class="add-btn-list btn btn-danger " data-dismiss="modal"> تراجع
                    </button>
                    <button type="submit" style="margin-left: 10px;"
                            class="add-btn-list btn btn-success confirmAddProviderToFeatured"> اضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#dynamic-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.lotteriesBranches') }}",
                columns: [
                    {name: 'name_ar'},
                    {name: 'name_en'},
                    {name: 'username'},
                    {name: 'mobile'},

                    {
                        name: 'provider.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'status'},
                    {name: 'created_at'},
                    {name: 'lottery_action', orderable: false, searchable: false}

                ],
            });
        });

        $(document).on('click', '.add_gift', function (e) {
            e.preventDefault();
             $('#providerName').empty().text($(this).attr('data_provider_name'));
            $('#providerId').val($(this).attr('data_provider_id'));
            $('#gift_Modal').modal('toggle');
        });

        $(document).on('click', '.remove_from_featured_provider', function (e) {
            e.preventDefault();

            $.ajax({
                type: 'post',
                url: "{{route('admin.branch.removeFromFeatured')}}",
                data: {
                    'provider_id': $(this).attr('data_provider_id'),
                },
                success: function (data) {
                    $('.featured'+data.branchId).removeClass('remove_from_featured_provider').addClass('add_to_featured_provider');
                    $('.featured'+data.branchId).removeClass("btn-warning").addClass('')
                }, error: function () {
                }
            });
        });


        $(document).on('click', '.add_to_lottery', function (e) {

            $.ajax({
                type: 'post',
                url: "{{route('admin.lotteriesBranches.add')}}",
                data: {
                    'provider_id': $(this).attr('data_provider_id'),
                },
                success: function (data) {
                    $('.lottery' + data.branchId).removeClass('add_to_lottery').addClass('remove_from_lottery');
                    $('.lottery' + data.branchId).removeClass("btn-default").addClass('btn-warning')
                }, error: function () {
                }
            });
        });

        $(document).on('click', '.remove_from_lottery', function (e) {
            e.preventDefault();

            $.ajax({
                type: 'post',
                url: "{{route('admin.lotteriesBranches.remove')}}",
                data: {
                    'provider_id': $(this).attr('data_provider_id'),
                },
                success: function (data) {
                    $('.featured' + data.branchId).removeClass('remove_from_lottery').addClass('add_to_lottery');
                    $('.featured' + data.branchId).removeClass("btn-warning").addClass('')
                }, error: function () {
                }
            });
        });

    </script>
@stop
