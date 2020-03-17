@extends('layouts.master')

@section('title', 'فواتير الحجوزات  ')

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('bills') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="col-md-9 page-header">
                <h1><i class="menu-icon fa fa-hospital-o"></i> فواتير الحجوزات  </h1>
            </div>
        </div>
        <div class="col-md-12">
            <table id="bill-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
                <thead>
                <tr>

                    <th>  رقم الحجز   </th>
                    <th> تاريخ رفع الفاتوره  </th>
                       <th>العمليات</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>



<!-- Modal -->
<div class="modal fade in" id="pointModal" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"> اضافة نقاط للمستخدم <span id="userName"></span> </h4>
            </div>
            <form action="{{Route('admin.bills.addPoints')}}" method="Post">
                {{csrf_field()}}
                <div class="modal-body">

                     <span  class="text-danger">  سعر النقطة بالريال: {{$price}} ريال </span>
                    <br> <br>
                    <input  onkeypress="return event.charCode >= 48" type="number" min="1" class="form-control" name="points"
                           placeholder="  ادخل عدد النقاط المضافه لصاحب الفاتوره  "/>
                    <input type="hidden" class="form-control" name="reservation_id" id="reservation_id" value=""
                           placeholder="   "/>
                    <input type="hidden" class="form-control" name="user_id" id="userId" value=""
                           placeholder="  "/>
                </div>
                <div class="modal-footer">
                    <button type="button" style="margin-left: 10px;" onclick=""
                            class="add-btn-list btn btn-danger " data-dismiss="modal">   تراجع
                    </button>
                    <button type="submit" style="margin-left: 10px;" onclick=""
                            class="add-btn-list btn btn-success confirmAddPoint"  >    اضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('scripts')
    <script>



        $(document).ready(function() {
            $('#bill-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.bills.data') }}",
                columns: [
                    {name: 'reservation_no'},
                    {name: 'created_at'},
                    {name: 'action', orderable: false, searchable: false}
                ],
            });
        });

        $(document).on('click', '.add_point_User', function (e) {
            e.preventDefault();
            $('#reservation_id').val($(this).attr('data_reser_id'));
            $('#userName').empty().text($(this).attr('data_user_name'));
            $('#userId').val($(this).attr('data_user_id'));
            $('#pointModal').modal('toggle');
        });

    </script>
@stop
