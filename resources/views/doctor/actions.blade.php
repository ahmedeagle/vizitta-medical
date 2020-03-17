<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.doctor.view', $doctor->id) }}" title="التفاصيل">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.doctor.edit', $doctor->id) }}" title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>

    @if($doctor->status)
        <div class="col-md-2">
            <a class="btn btn-white btn-warning btn-lg" href="{{ route('admin.doctor.status', [$doctor->id, 0]) }}" title="إلغاء تفعيل">
                <i class="fa fa-times"></i>
            </a>
        </div>
    @else
        <div class="col-md-2">
            <a class="btn btn-white btn-success btn-lg" href="{{ route('admin.doctor.status', [$doctor->id, 1]) }}" title="تفعيل">
                <i class="fa fa-check"></i>
            </a>
        </div>
    @endif

    @if(count($doctor->reservations) == 0)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.doctor.delete', $doctor->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    @endif
</div>