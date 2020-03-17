@section('main')
    {{ Form::select('doctor_id', $doctors, old('doctor_id'), ['multiple'=>'multiple','name'=>'doctorsIds[]','style'=>'height: 100px !important;','class' => 'js-example-basic-multiple form-control ' . ($errors->has('doctor_id') ? 'redborder' : '') ]) }}
    <label for="provider_id">الاطباء </label>
    <small class="text-danger">{{ $errors->has('doctor_id') ? $errors->first('doctor_id') : '' }}</small>
@stop
