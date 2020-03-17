<div class="actions">
        <div class="col-md-2">
            <a data_provider_id="{{ $branch  -> id}}" data_provider_name="{{ $branch  -> name_ar}}" class="btn btn-white btn-danger btn-lg add_gift" href=""
               title=" أضافه هديه ">
                <i class="fa fa-plus"></i>
            </a>
        </div>

    <div class="col-md-2">
        <a href="{{route('admin.lotteriesBranches.showBranchGifts',$branch  -> id)}}" data_provider_id="{{ $branch  -> id}}" data_provider_name="{{ $branch  -> name_ar}}"
           class="btn btn-white  btn-warning btn-lg "
           title="عرض الهدايا الحالية">
            <i class="fa fa-gift"></i>
        </a>
    </div>

</div>
