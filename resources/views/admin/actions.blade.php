<div class="actions">
         <div class="col-md-2">
            <a class="btn btn-white btn-primary btn-lg" href="{{ route('admin.data.provider.branches.balance', $provider->id) }}" title="تعديل">
                <i class="fa fa-pencil"></i>
            </a>
        </div>


    <div class="col-md-2">
        <a class="btn btn-white btn-danger btn-lg" href="{{ route('admin.provider.reservations', $provider->id) }}" title=" سجل الحجوزات ">
            <i class="fa fa-ticket"></i>
        </a>
    </div>
 </div>
