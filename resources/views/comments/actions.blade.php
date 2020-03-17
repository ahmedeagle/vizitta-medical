<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg"    href="{{ route('admin.notifications.recievers', [$report->id]) }}" title="عرض مستقبلي الاشعارات ">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    <div class="col-md-2">
        <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.notifications.delete', $notify->id) }}" title="مسح">
            <i class="fa fa-trash"></i>
        </a>
    </div>
</div>
