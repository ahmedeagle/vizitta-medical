 <div class="actions">
         <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.comments.delete', $reservation->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>

    <div class="col-md-2">
        <a class="btn btn-white btn-danger btn-lg edit_rate"  data_reservation_id="{{$reservation  -> id }}" href="" title="تعديل">
            <i class="fa fa-edit"></i>
        </a>
    </div>

 </div>

