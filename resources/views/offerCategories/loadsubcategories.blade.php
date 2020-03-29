@section('main')
    @if(isset($subcategories) && $subcategories -> count() > 0)
        <option value="0">الكل</option>
        @foreach($subcategories as $subcategory )
           <option value="{{$subcategory -> id}}">{{$subcategory -> name_ar}}</option>
        @endforeach
     @endif
@stop
