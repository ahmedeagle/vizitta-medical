@extends('layouts.master')

@section('title', 'تعديل نص الإتفاقية')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('edit.agreement') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-pencil"></i> تعديل نص الإتفاقية </h1>
            </div>
        </div>

        <div class="col-md-12">
            {{ Form::model($agreement, ['route' => 'admin.data.agreement.update', 'class' => 'form', 'method' => 'PUT']) }}
                <div class="form-group col-sm-12">
                    <label for="agreement_ar">نص الموافقة بالعربية <span class="astric">*</span></label>
                    {{ Form::textarea('agreement_ar', old('agreement_ar'), ['placeholder' => 'نص الموافقة بالعربية', 'id' => 'agreement_ar' , 'class' => 'form-control ' . ($errors->has('agreement_ar') ? 'redborder' : '') ]) }}
                    <small class="text-danger">{{ $errors->has('agreement_ar') ? $errors->first('agreement_ar') : '' }}</small>
                </div>

                <div class="form-group col-sm-12">
                    <label for="agreement_en">نص الموافقة بالإنجليزية <span class="astric">*</span></label>
                    {{ Form::textarea('agreement_en', old('agreement_en'), ['placeholder' => 'نص الموافقة بالإنجليزية', 'id' => 'agreement_en' , 'class' => 'form-control ' . ($errors->has('agreement_en') ? 'redborder' : '') ]) }}
                    <small class="text-danger">{{ $errors->has('agreement_en') ? $errors->first('agreement_en') : '' }}</small>
                </div>

                <div class="form-group col-sm-12">
                    <label for="reservation_rules_ar">شروط الحجز بالعربية <span class="astric">*</span></label>
                    {{ Form::textarea('reservation_rules_ar', old('reservation_rules_ar'), ['placeholder' => 'شروط الحجز بالعربية', 'id' => 'reservation_rules_ar' , 'class' => 'form-control ' . ($errors->has('reservation_rules_ar') ? 'redborder' : '') ]) }}
                    <small class="text-danger">{{ $errors->has('reservation_rules_ar') ? $errors->first('reservation_rules_ar') : '' }}</small>
                </div>

                <div class="form-group col-sm-12">
                    <label for="reservation_rules_en">شروط الحجز بالإنجليزية <span class="astric">*</span></label>
                    {{ Form::textarea('reservation_rules_en', old('reservation_rules_en'), ['placeholder' => 'شروط الحجز بالإنجليزية', 'id' => 'reservation_rules_en',   'class' => 'form-control ' . ($errors->has('reservation_rules_en') ? 'redborder' : '') ]) }}
                    <small class="text-danger">{{ $errors->has('reservation_rules_en') ? $errors->first('reservation_rules_en') : '' }}</small>
                </div>



            <div class="form-group col-sm-12">
                <label for="reservation_rules_ar"> شروط تسجيل مقدم الخدمة  بالعربية<span class="astric">*</span></label>
                {{ Form::textarea('provider_reg_rules_ar', old('provider_reg_rules_ar'), ['placeholder' => 'شروط  التسجيل بالعربية', 'id' => 'provider_reg_rules_ar',   'class' => 'form-control ' . ($errors->has('provider_reg_rules_ar') ? 'redborder' : '') ]) }}
                <small class="text-danger">{{ $errors->has('provider_reg_rules_ar') ? $errors->first('provider_reg_rules_ar') : '' }}</small>
            </div>

            <div class="form-group col-sm-12">
                <label for="reservation_rules_en"> شروط تسجيل مقدم الخدمة بالانجليزية<span class="astric">*</span></label>
                {{ Form::textarea('provider_reg_rules_en', old('provider_reg_rules_en'), ['placeholder' => 'شروط  التسجيل بالإنجليزية', 'id' => 'provider_reg_rules_en',   'class' => 'form-control ' . ($errors->has('provider_reg_rules_ar') ? 'redborder' : '') ]) }}
                <small class="text-danger">{{ $errors->has('provider_reg_rules_en') ? $errors->first('provider_reg_rules_en') : '' }}</small>
            </div>

                <div class="form-group col-sm-12 submit">
                    {{ Form::submit('حفظ', ['class' => 'btn btn-sm']) }}
                </div>
            {{ Form::close() }}
        </div>
    </div>
@stop

@section('popup')
    <p>من فضلك إدخل جميع الحقول المطلوبة</p>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $("textarea").each(function() {
                var editor = CKEDITOR.replace($(this).attr('id'), {
                    language: 'ar',
                }).on('required', function( evt ) {
                    $('.hover_popup').show();
                    evt.cancel();
                });
            });
        });
    </script>
@stop
