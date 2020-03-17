@section('main')

    @if(isset($branches) && $branches -> count() > 0)
        @foreach($branches as $branch )
           <option value="{{$branch -> id}}" @if($branch -> selected == 1) selected="" @endif>{{$branch -> name_ar}}</option>
        @endforeach
     @endif
@stop
