<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.promoCode.view', $promoCode->id) }}" title="التفاصيل">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    <div class="col-md-2"  >
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.promoCode.edit', $promoCode->id) }}" title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>


 <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.promoCode.branches', $promoCode->id) }}" title="الافرع ">
            <i class="fa fa-hospital-o"></i>
        </a>
    </div>


 <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.promoCode.doctors', $promoCode->id) }}" title="الاطباء ">
            <i class="fa fa-user-md"></i>
        </a>
    </div>


{{--
    @if(count($promoCode->reservations) == 0)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.promoCode.delete', $promoCode->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    @endif
    --}}
</div>
