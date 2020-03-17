@section('main')
    {{ Form::select('branch_id', $branches, old('branch_id'), ['multiple'=>'multiple','name'=>'branchIds[]' , 'id'=>"branches", 'class' => 'js-example-basic-multiple form-control ' . ($errors->has('branch_id') ? 'redborder' : ''), 'style'=>'height: 100px !important;' ]) }}
    <label for="provider_id"> الأفرع </label>
    <small class="text-danger">{{ $errors->has('branch_id') ? $errors->first('branch_id') : '' }}</small>
@stop
