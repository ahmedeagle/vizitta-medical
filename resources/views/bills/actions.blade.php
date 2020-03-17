<div class="actions">

        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.bills.delete', $bill->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>


    <div class="col-md-2">
        <a class="btn btn-white btn-success btn-lg" href="{{ route('admin.bills.show', $bill->id) }}" title=" عرض">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    <div class="col-md-2">
        <a  data_user_id="{{ $bill -> reservation -> user -> id}}"  data_user_name="{{ $bill -> reservation -> user -> name}}" data_reser_id="{{$bill -> reservation -> id}}" class="btn btn-white btn-success btn-lg add_point_User"  title="  اضافة نقاط">
            <i class="fa fa-plus-circle"></i>
        </a>
    </div>


</div>
