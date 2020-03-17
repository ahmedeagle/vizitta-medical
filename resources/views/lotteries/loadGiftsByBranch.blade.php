@section('main')
    @if(isset($gifts) && $gifts -> count() > 0)
        @foreach($gifts as $index =>  $gift)
            <div class="col-md-4">
                <label class="radio-inline" style="user-select: none;">
                    <input style="margin: -8px -19px 0 0" data-id="{{$gift -> id}}" class="giftdraw"
                           type="radio" name="gifts[]" style=" display: inline-block"
                           value="{{$gift -> id}}"> {{$gift -> title}}
                </label>
            </div>
        @endforeach
    @endif
@stop
