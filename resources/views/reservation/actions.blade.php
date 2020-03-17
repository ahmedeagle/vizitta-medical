<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.reservation.view', $reservation->id) }}" title="التفاصيل">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    @if($reservation->approved == 0)
        <div class="col-md-2">
            <a class="btn btn-white btn-success btn-lg" href="{{ route('admin.reservation.status', [$reservation->id, 1]) }}" title="قبول">
                <i class="fa fa-check"></i>
            </a>
        </div>
        <div class="col-md-2">
            <a data_reser_id="{{$reservation->id}}"  data_reser_status="2" class="btn btn-white btn-warning btn-lg reject_reason_btn" href="{{ route('admin.reservation.status', [$reservation->id, 2]) }}" title="رفض">
                <i class="fa fa-times"></i>
            </a>
        </div>
    @endif

    @if($reservation->approved != 1)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.reservation.delete', $reservation->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    @endif


    @if($reservation->approved  == 0 or  $reservation->approved == 1)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.reservation.update', $reservation->id) }}" title=" تعديل موعد ">
                <i class="fa fa-pencil"></i>
            </a>
        </div>
    @endif

</div>
