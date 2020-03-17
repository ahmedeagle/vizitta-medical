<div class="modal fade in" id="modalFilterForm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 class="modal-title w-100 font-weight-bold"> فلتره نتائج الحجوزات - {{@$provider_name}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{route('admin.provider.reservations',$provider_id)}}" method="GET">
                <div class="modal-body mx-3">

                    <div class="md-form mb-5">
                        <label data-error="wrong" data-success="right" for="form34">من تاريخ </label>
                        <input type="date" name="from_date" id="form34" class="form-control validate">

                    </div>
                    <div class="md-form mb-5">

                        <label data-error="wrong" data-success="right" for="form29"> الي تاريخ </label>
                        <input type="date" id="form29" name="to_date" class="form-control validate">

                    </div>
                    <div class="md-form mb-5">
                        <label data-error="wrong" data-success="right" for="form29"> الطبيب المحجوز لديه </label>
                        <select name="doctor_id" class="form-control">
                            @if(isset($doctorsIds) && count($doctorsIds) > 0 )
                                @foreach($doctorsIds as $name  => $doctor_id)
                                    <option value="{{$doctor_id}}">{{$name}}</option>
                                @endforeach
                            @endif
                        </select>

                    </div>
                  {{--  <div class="md-form mb-5">
                        <label data-error="wrong" data-success="right" for="form29"> الفرع المحجوز الية </label>
                        <select name="branch_id" class="form-control">
                            @if(isset($branchsIds) && count($branchsIds) > 0 )
                                @foreach($branchsIds as $name  => $branch_id)
                                    <option value="{{$branch_id}}">{{$name}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div> --}}
                    <div class="md-form mb-5">
                        <label data-error="wrong" data-success="right" for="form29"> طريقه الدفع </label>
                        <select name="payment_method_id" class="form-control">
                            @if(isset($paymentMethodIds) && count($paymentMethodIds) > 0 )
                                @foreach($paymentMethodIds as $name  => $paymentMethod_id)
                                    <option value="{{$paymentMethod_id}}">{{$name}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                </div>
                <div class="modal-footer d-flex justify-content-center">
                    <button type="submit" class="btn btn-unique"> أبحث <i class="fa fa-search ml-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>
