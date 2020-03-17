<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.branch.view', $branch->id) }}"
           title="التفاصيل">
            <i class="fa fa-eye"></i>
        </a>
    </div>
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.branch.edit', $branch->id) }}"
           title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>
    @if($branch->status)
        <div class="col-md-2">
            <a class="btn btn-white btn-warning btn-lg" href="{{ route('admin.branch.status', [$branch->id, 0]) }}"
               title="إلغاء تفعيل">
                <i class="fa fa-times"></i>
            </a>
        </div>
    @else
        <div class="col-md-2">
            <a class="btn btn-white btn-success btn-lg" href="{{ route('admin.branch.status', [$branch->id, 1]) }}"
               title="تفعيل">
                <i class="fa fa-check"></i>
            </a>
        </div>
    @endif
    @if(count($branch->reservations) == 0)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.branch.delete', $branch->id) }}"
               title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    @endif
    <div class="col-md-2">
        <a data_provider_id="{{ $branch  -> id}}" data_provider_name="{{ $branch  -> name_ar}}"
           class="btn btn-white featured{{$branch -> id}} @if($branch -> subscriptions -> where('expired',0) -> first()) btn-warning  remove_from_featured_provider @else btn-default  add_to_featured_provider @endif btn-lg "
           title="تثبيت العياده ">
            <i class="fa fa-star"></i>
        </a>
    </div>

</div>
