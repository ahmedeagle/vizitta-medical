<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.customPage.edit', $customPage->id) }}" title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>
    
    @if($customPage->status)
        <div class="col-md-2">
            <a class="btn btn-white btn-warning btn-lg" href="{{ route('admin.customPage.status', [$customPage->id, 0]) }}" title="إلغاء نشر">
                <i class="fa fa-times"></i>
            </a>
        </div>
    @else
        <div class="col-md-2">
            <a class="btn btn-white btn-success btn-lg" href="{{ route('admin.customPage.status', [$customPage->id, 1]) }}" title="نشر">
                <i class="fa fa-check"></i>
            </a>
        </div>
    @endif
    
    <div class="col-md-2">
        <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.customPage.delete', $customPage->id) }}" title="مسح">
            <i class="fa fa-trash"></i>
        </a>
    </div>
</div>