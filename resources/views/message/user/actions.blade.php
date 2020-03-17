<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.user.message.view', $message->id) }}"
           title="التفاصيل">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    <div class="col-md-2">
        <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.user.message.delete', $message->id) }}"
           title="مسح">
            <i class="fa fa-trash"></i>
        </a>
    </div>

    @if($message -> solved == 0)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.user.message.solved', $message->id) }}"
               title="  تم الحل ">
                <i class="fa fa-check"></i>
            </a>
        </div>
    @endif
</div>
