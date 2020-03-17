<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.user.view', $user->id) }}" title="التفاصيل">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    @if($user->status)
        <div class="col-md-2">
            <a class="btn btn-white btn-warning btn-lg" href="{{ route('admin.user.status', [$user->id, 0]) }}" title="إلغاء تفعيل">
                <i class="fa fa-times"></i>
            </a>
        </div>
    @else
        <div class="col-md-2">
            <a class="btn btn-white btn-success btn-lg" href="{{ route('admin.user.status', [$user->id, 1]) }}" title="تفعيل">
                <i class="fa fa-check"></i>
            </a>
        </div>
    @endif

    @if(count($user->reservations) == 0)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.user.delete', $user->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    @endif
</div>
