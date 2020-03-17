<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.city.edit', $city->id) }}" title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>

    @if(count($city->providers) == 0)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.city.delete', $city->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    @endif
</div>