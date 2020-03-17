@section('main')
    @if(isset($users) && $users -> count() > 0)
        @foreach($users as $index =>  $user)
            <tr>
                <td>{{$user -> name}}</td>
                <td>{{maskPhoneNumber($user -> mobile)}}</td>
            </tr>
        @endforeach
    @endif
@stop
