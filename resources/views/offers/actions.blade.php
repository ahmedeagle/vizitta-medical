<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.offers.view', $offer->id) }}"
           title="التفاصيل">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.offers.edit', $offer->id) }}"
           title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>


    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.offers.branches', $offer->id) }}"
           title="الافرع ">
            <i class="fa fa-hospital-o"></i>
        </a>
    </div>


    {{--<div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.offers.doctors', $offer->id) }}"
           title="الاطباء ">
            <i class="fa fa-user-md"></i>
        </a>
    </div>--}}


    {{--
        @if(count($offer->reservations) == 0)
            <div class="col-md-2">
                <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.promoCode.delete', $offer->id) }}" title="مسح">
                    <i class="fa fa-trash"></i>
                </a>
            </div>
        @endif
        --}}
</div>
