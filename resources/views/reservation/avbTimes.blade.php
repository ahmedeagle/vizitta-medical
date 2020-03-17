@section('main')

    @if(isset($times) && count($times) > 0 )
    <ul style="list-style: none;dir: rtl">
        @foreach($times as $time)
            <li data_date="{{$time -> date}}" data_from="{{$time -> from_time}}" data_to="{{$time -> to_time}}"  class="resTime" style="display:inline;padding: 5px; float: right"> <a style="color:#000" href="#">{{$time -> from_time}} - {{$time -> to_time}} </a> </li>
        @endforeach
    </ul>
    @endif
    @stop

