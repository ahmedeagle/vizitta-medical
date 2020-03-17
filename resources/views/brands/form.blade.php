<div class="form-group has-float-label col-sm-6">
    {{ Form::file('photo', ['class' => 'form-control ' . ($errors->has('photo') ? 'redborder' : '') ]) }}
    <label for="title">صوره  الشعار  <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('photo') ? $errors->first('photo') : '' }}</small>
</div>

<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>
