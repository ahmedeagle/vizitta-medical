<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.reasons.edit', $reason->id) }}" title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>

         <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.reasons.delete', $reason->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>
 </div>
