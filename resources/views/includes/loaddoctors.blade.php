@section('main')

    @if(isset($doctors) && $doctors -> count() > 0)

            @foreach($doctors as $doctor )
                <option value="{{$doctor -> id}}" @if($doctor -> selected == 1) selected="" @endif>{{$doctor -> name_ar}}</option>
            @endforeach
    @endif

@stop

