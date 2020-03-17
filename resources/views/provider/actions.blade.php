<div class="actions">
    <div class="col-md-2">
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.provider.view', $provider->id) }}" title="التفاصيل">
            <i class="fa fa-eye"></i>
        </a>
    </div>

    <div class="col-md-2" >
        <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.provider.edit', $provider->id) }}" title="تعديل">
            <i class="fa fa-pencil"></i>
        </a>
    </div>

    @if($provider->status)
        <div class="col-md-2">
            <a class="btn btn-white btn-warning btn-lg" href="{{ route('admin.provider.status', [$provider->id, 0]) }}" title="إلغاء تفعيل">
                <i class="fa fa-times"></i>
            </a>
        </div>
    @else
        <div class="col-md-2">
            <a class="btn btn-white btn-success btn-lg" href="{{ route('admin.provider.status', [$provider->id, 1]) }}" title="تفعيل">
                <i class="fa fa-check"></i>
            </a>
        </div>
    @endif

    @php($count=0)
    @foreach($provider->providers as $branch)
        @if(count($branch->reservations) > 0)
            @php($count++)
        @endif
    @endforeach

    @if(count($provider->reservations) == 0 && $count == 0)
        <div class="col-md-2">
            <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.provider.delete', $provider->id) }}" title="مسح">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    @endif

    <div class="col-md-2">
        <a data_provider_id="{{ $provider  -> id}}" data_provider_name="{{ $provider  -> name_ar}}"
           class="btn btn-white lottery{{$provider -> id}} @if($provider -> lottery  == 1) btn-warning  remove_from_lottery @else btn-default  add_to_lottery @endif btn-lg "
           title="سحب عشوائي ">
            <i class="fa fa-gift"></i>
        </a>
    </div>
</div>
