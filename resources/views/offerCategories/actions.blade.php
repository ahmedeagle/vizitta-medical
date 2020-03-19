<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.offerCategories.edit', $category->id) }}"
           title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>
    {{--
    <div class="col-md-2">
        <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.offerCategories.delete', $category->id) }}"
           title="مسح">
            <i class="fa fa-trash"></i>
        </a>
    </div>
    --}}

    <div class="col-md-2">
        <a data_category_id="{{ $category  -> id}}" data_category_name="{{ $category  -> name_ar}}"
           class="add_to_timer btn btn-white btn-danger btn-lg timer{{$category -> id}}"
           title="اضافه عداد تنازلي">
            <i class="fa fa-clock-o"></i>
        </a>
    </div>

</div>
