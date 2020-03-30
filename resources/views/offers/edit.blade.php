@extends('layouts.master')

@section('title', 'تعديل عرض')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet"/>
    {!! Html::style('css/form.css') !!}

    <style>
        .select2-container .select2-search--inline {
            float: right;
            text-align: center;
            position: fixed;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            padding-top: 5px;
        }
    </style>
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.offers') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل عرض </h1>
        </div>
    </div>


    {{ Form::model($offer, ['route' => ['admin.offers.update' , $offer->id], 'class' => 'form', 'method' => 'PUT','files' => true]) }}

    <div class="form-group has-float-label col-sm-12" style="padding-bottom: 8px;">
        <select id="parent_categories" name="category_ids[]" multiple="multiple"
                class='js-example-basic-multiple form-control '
                . {{$errors->has('category_id') ? 'redborder' : ''}}>
            <optgroup label="أختر أقسام العرض">
                @if(isset($categories)&& $categories -> count() > 0 )
                    @foreach($categories as $category)
                        <option value="{{$category -> id}}"
                                @if($category -> selected == 1) selected @endif>{{$category -> name_ar}} {{$category -> hastimer ? '(عداد تنازلي)': ''}}</option>
                    @endforeach
                @endif
            </optgroup>
        </select>
        <label for="status">القسم الرئيسى<span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('category_id') ? $errors->first('category_id') : '' }}</small>
    </div>

    <div id="childCategories">
        @if (count($categories) > 0)
            @foreach($categories as $category)
                @if ($category->selected == 1)
                    <div id="child-{{ $category->id }}" class="form-group has-float-label col-sm-6"
                         style="padding-bottom: 8px;">
                        <h3># {{ $category->name_ar }}: الاقسام الفرعية </h3>
                        @foreach($category->childCategories as $key => $value)
                            <input name="child_category_ids[]" type="checkbox" class="ace"
                                   {{ $value->selected == 1 ? 'checked' : '' }} value="{{ $value->id }}"><span
                                class="lbl"> {{ $value->name_ar }} </span>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endif
    </div>
    <div class="clearfix"></div>
    <br>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::text('title_ar', old('title_ar'), ['placeholder' => 'خصم كبير علي الحجوزات ',  'class' => 'form-control ' . ($errors->has('title_ar') ? 'redborder' : '') ]) }}
        <label for="title"> عنوان الكوبون بالعربي <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('title_ar') ? $errors->first('title_ar') : '' }}</small>
    </div>
    <div class="form-group has-float-label col-sm-6">
        {{ Form::text('title_en', old('title_en'), ['placeholder' => 'خصم كبير علي الحجوزات ',  'class' => 'form-control ' . ($errors->has('title_en') ? 'redborder' : '') ]) }}
        <label for="title"> عنوان الكوبون بالانجليزي <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('title_en') ? $errors->first('title_en') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::file('photo', ['class' => 'form-control ' . ($errors->has('photo') ? 'redborder' : '') ]) }}
        <label for="title">صوره الكوبون <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('photo') ? $errors->first('photo') : '' }}</small>
    </div>

    <div id="cop_pricee" class="form-group has-float-label col-sm-6">
        {{ Form::number('price', old('price'), ['placeholder' => 'ادخل السعر في حاله الكوبون فقط ','class' => 'form-control ' . ($errors->has('price') ? 'redborder' : '') ]) }}
        <label for="price"> سعر الكوبون </label>
        <small class="text-danger">{{ $errors->has('price') ? $errors->first('price') : '' }}</small>
    </div>

    <div id="cop_pricee" class="form-group has-float-label col-sm-6">
        {{ Form::number('price_after_discount', old('price_after_discount'), ['placeholder' => 'ادخل السعر  بعد الخصم','class' => 'form-control ' . ($errors->has('price_after_discount') ? 'redborder' : '') ]) }}
        <label for="price"> السعر بعد الخصم<span class="astric">*</span> </label>
        <small
            class="text-danger">{{ $errors->has('price_after_discount') ? $errors->first('price_after_discount') : '' }}</small>
    </div>

    <div id="cop_percg" class="form-group has-float-label col-sm-6">
        {{ Form::number('paid_coupon_percentage', old('paid_coupon_percentage'), ['placeholder' => 'ادخل  نسبة الاداره من الكوبون ','class' => 'form-control ' . ($errors->has('paid_coupon_percentage') ? 'redborder' : '') ]) }}
        <label for="price"> نسبة الاداره من الكوبون<span class="astric">*</span> </label>
        <small
            class="text-danger">{{ $errors->has('paid_coupon_percentage') ? $errors->first('paid_coupon_percentage') : '' }}</small>
    </div>

    <div id="copounCode" class="form-group has-float-label col-sm-6">
        {{ Form::text('code', old('code'), ['placeholder' => 'الرمز',  'class' => 'form-control ' . ($errors->has('code') ? 'redborder' : '') ]) }}
        <label for="code">الرمز <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('code') ? $errors->first('code') : '' }}</small>
    </div>

    <div id="cop_discount" class="form-group has-float-label col-sm-6">
        {{ Form::text('discount', old('discount'), ['placeholder' => 'الخصم',  'class' => 'form-control ' . ($errors->has('discount') ? 'redborder' : '') ]) }}
        <label for="discount">الخصم <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('discount') ? $errors->first('discount') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::number('available_count', old('available_count'), ['placeholder' => 'العدد المتاح',  'class' => 'form-control ' . ($errors->has('available_count') ? 'redborder' : '') ]) }}
        <label for="available_count">العدد المتاح <span class="astric">*</span></label>
        <small
            class="text-danger">{{ $errors->has('available_count') ? $errors->first('available_count') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::select('available_count_type', ['once' => 'مرة واحدة', 'more_than_once' => 'اكثر من مرة'], old('available_count_type'), ['placeholder' => 'نوع العدد المتاح',  'class' => 'form-control ' . ($errors->has('available_count_type') ? 'redborder' : '') ]) }}
        <label for="available_count_type">نوع العدد المتاح <span class="astric">*</span></label>
        <small
            class="text-danger">{{ $errors->has('available_count_type') ? $errors->first('available_count_type') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::date('started_at', old('started_at'), ['placeholder' => 'تاريخ البداية',  'class' => 'form-control ' . ($errors->has('started_at') ? 'redborder' : '') ]) }}
        <label for="started_at">تاريخ البداية <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('started_at') ? $errors->first('started_at') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::date('expired_at', old('expired_at'), ['placeholder' => 'تاريخ الإنتهاء',  'class' => 'form-control ' . ($errors->has('expired_at') ? 'redborder' : '') ]) }}
        <label for="expired_at">تاريخ الإنتهاء <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('expired_at') ? $errors->first('expired_at') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::select('gender', ['all' => 'رجال ونساء', 'males' => 'رجال فقط', 'females' => 'نساء فقط'], old('gender'), ['class' => 'form-control ' . ($errors->has('gender') ? 'redborder' : '') ]) }}
        <label for="gender">الجنس <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('gender') ? $errors->first('gender') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'الحالة',  'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
        <label for="status">الحالة <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6" id="app_perc" style="display: none;">
        {{ Form::number('application_percentage', old('application_percentage'), ['placeholder' => ' نسبة التطبيق', 'class' => 'form-control ' . ($errors->has('application_percentage') ? 'redborder' : '') ]) }}
        <label for="street"> نسبة التطبق من العرض لهذا التاجر</label>
        <small
            class="text-danger">{{ $errors->has('application_percentage') ? $errors->first('application_percentage') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::select('featured', $featured, old('featured'), ['name'=>'featured' ,'class' => 'form-control ' . ($errors->has('featured') ? 'redborder' : '') ]) }}
        <label for="provider_id"> تمييز العرض </label>
        <small class="text-danger">{{ $errors->has('featured') ? $errors->first('featured') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        {{ Form::text('device_type', old('device_type'), ['placeholder' => 'نوع الجهاز ',  'class' => 'form-control ' . ($errors->has('device_type') ? 'redborder' : '') ]) }}
        <label for="title"> نوع الجهاز </label>
        <small class="text-danger">{{ $errors->has('device_type') ? $errors->first('device_type') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-6">
        <select multiple='multiple' data-live-search='true' name='users[]'
                class='js-example-basic-multiple form-control ' . ($errors->has('users') ? 'redborder' : '')>
            @if(!empty($users) && count($users) >0)
                @foreach($users as    $user)
                    <option value="{{$user -> id}}"
                            @if($user -> selected == 1) selected @endif>{{$user -> name}}</option>
                @endforeach
            @endif
        </select>
        <label for="provider_id"> تخصيص مستخدمي العرض </label>
        <small class="text-danger">{{ $errors->has('users') ? $errors->first('users') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-12">
        {{ Form::select('provider_id', $providers, old('provider_id'), [ 'data-live-search'=> 'true','placeholder' => 'مقدم الخدمة','id' => 'providers', 'class' => 'form-control js-example-basic-single' . ($errors->has('provider_id') ? 'redborder' : '') ]) }}
        <label for="provider_id">مقدم الخدمة</label>
        <small class="text-danger">{{ $errors->has('provider_id') ? $errors->first('provider_id') : '' }}</small>
    </div>

    <div class="form-group has-float-label col-sm-12 ">
        <select name="branchIds[]" style='height: 100px !important;' id="branches" multiple='multiple'
                class='appendbrnaches js-example-basic-multiple form-control ' {{$errors->has('branch_id') ? 'redborder' : ''}}>
        </select>
        <label for="branches"> الأفرع </label>
        <small class="text-danger">{{ $errors->has('branchIds') ? $errors->first('branchIds') : '' }}</small>
    </div>

    <div id="branchTimesDiv" style="margin-bottom: 50px"></div>
    <div class="clearfix"></div>
    <hr>
    <br>
    {{--<label> وسائل الدفع </label>--}}
    <div class="row form-group has-float-label">
        @if(isset($paymentMethods) && $paymentMethods -> count() > 0)
            @foreach($paymentMethods as $index => $paymentMethod)
                <div class="col-md-4">
                    <label class="checkbox-inline" style="user-select: none">
                        <input style="margin: -8px -19px 0 0 " type="checkbox" id="payment_method"
                               name="payment_method[]"
                               {{ (is_array(old('payment_method')) && in_array($paymentMethod->id, old('payment_method'))) ? ' checked' : '' }} @if($paymentMethod -> selected == 1) checked
                               @endif style=" display: inline-block"
                               value="{{$paymentMethod -> id}}"> {{$paymentMethod -> name_ar}}
                    </label>
                </div>
            @endforeach
        @endif
        <small class="text-danger">{{ $errors->has('payment_method') ? $errors->first('payment_method') : '' }}</small>
    </div>
    <br>

    @php
        if (isset($offer)){
            foreach($offer->paymentMethods as $pMethod){
                if ($pMethod->id == 6){
                    $checkElectronicPayment = true;
                    break;
                }
                $checkElectronicPayment = false;
            }
        }
    @endphp

    @if ($checkElectronicPayment)
        <div class="form-group has-float-label col-sm-6" id="amountTypeDiv">
            <select name="payment_amount_type" id="payment_amount_type" class="form-control">
                <option value="all" {{ $pMethod->pivot->payment_amount_type == 'all' ? 'selected' : '' }}>المبلغ
                    كامل
                </option>
                <option value="custom" {{ $pMethod->pivot->payment_amount_type == 'custom' ? 'selected' : '' }}>مبلغ
                    معين
                </option>
            </select>
            <label for="payment_amount_type"> نوع المبلغ </label>
        </div>

        <div class="form-group has-float-label col-sm-6"
             style="{{ $pMethod->pivot->payment_amount_type == 'custom' ? '': 'display: none;' }}"
             id="customAmountDiv">
            {{ Form::number('payment_amount', $pMethod->pivot->payment_amount, ['name' => 'payment_amount', 'placeholder' => 'المبلغ',  'class' => 'form-control ' . ($errors->has('payment_amount') ? 'redborder' : '') ]) }}
            <label for="title"> المبلغ </label>
            <small
                class="text-danger">{{ $errors->has('payment_amount') ? $errors->first('payment_amount') : '' }}</small>
        </div>
    @else
        <div class="form-group has-float-label col-sm-6" style="display: none;" id="amountTypeDiv">
            {{ Form::select('payment_amount_type', ['all' => 'المبلغ كامل', 'custom' => 'مبلغ معين'], old('payment_amount_type'), ['id'=>'payment_amount_type', 'name'=>'payment_amount_type' ,'class' => 'form-control', '']) }}
            <label for="payment_amount_type"> نوع المبلغ </label>
        </div>

        <div class="form-group has-float-label col-sm-6" style="display: none;" id="customAmountDiv">
            {{ Form::number('payment_amount', old('payment_amount'), ['placeholder' => 'المبلغ',  'class' => 'form-control ' . ($errors->has('payment_amount') ? 'redborder' : '') ]) }}
            <label for="title"> المبلغ </label>
            <small
                class="text-danger">{{ $errors->has('payment_amount') ? $errors->first('payment_amount') : '' }}</small>
        </div>
    @endif

    <hr>
    <br>

    @foreach($offerContents as $index => $offerCont)
        <div class="form-group has-float-label offer-content" id="contentBox_{{$offerCont->id}}"
             style="padding-top: 30px">
            <div class="col-sm-6">
                <label for="title"> المحتوى بالعربية </label>
                <input type="text" name="offer_content[ar][]" placeholder="المحتوى بالعربية" style="width: 100%;"
                       value="{{ $offerCont->content_ar }}">
            </div>
            <div class="col-sm-6">
                <label for="title"> المحتوى بالإنجليزية </label>
                <input type="text" name="offer_content[en][]" placeholder="المحتوى بالإنجليزية" style="width: 73%;"
                       value="{{ $offerCont->content_en }}">

                @if ($index == 0)
                    <button type="button" id="" class="btnAddMoreContent btn btn-success sm"><i
                            class="menu-icon fa fa-plus-circle fa-fw"></i></button>
                @else
                    <button type="button" class="btnDeleteContent btn btn-danger sm"
                            onclick="deleteContentBox({{$offerCont->id}})"><i
                            class="menu-icon fa fa-trash-o fa-fw"></i></button>
                @endif

            </div>
        </div>
    @endforeach

    <div class="form-group has-float-label offer-content" id="allContentDivs"></div>

</div>

<div class="form-group col-sm-12 submit">
    {{ Form::submit('تحديث', ['class' => 'btn btn-sm' ]) }}
</div>

{{ Form::close() }}
@stop


@section('extra_scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>

    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        //get provider branches
        $(document).on('change', '#providers', function (e) {
            e.preventDefault();
            $('.appenddoctors').empty();
            $('.appendbranches').empty();
            $('#branchTimesDiv').empty();

            $.ajax({

                type: 'post',
                url: "{{Route('admin.offers.providerbranches')}}",
                data: {
                    'parent_id': $(this).val(),
                    //'_token'   :   $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (data) {
                    $('.appendbrnaches').empty().append(data.content);
                }
            });
        });

        $(document).ready(function () {

            $.ajax({
                type: 'post',
                url: "{{Route('admin.offers.providerbranches')}}",
                data: {
                    'parent_id': $('#providers').val(),
                    'couponId': "{{$offer -> id}}",
                    //'_token'   :   $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (data) {
                    $('.appendbrnaches').empty().append(data.content);

                    var offerBranchTimes = data.offerBranchTimes;
                    var allBranchTimes = $('#branchTimesDiv');

                    for (let branch in offerBranchTimes) {

                        var body = `<div id="branchTimeBox_${branch}" class="form-group has-float-label col-sm-6">
                            <h3># ${offerBranchTimes[branch].branch_name} #</h3>
                            <input type="number" name="branchTimes[${branch}][duration]" value="${offerBranchTimes[branch].duration}" placeholder="مدة الإنتظار" style="width: 100%">
                            <table class="table">
                                    <thead>
                                      <tr>
                                        <th>اليوم</th>
                                        <th>من</th>
                                        <th>الى</th>
                                      </tr>
                                    </thead>
                                    <tbody>`;

                        for (let day of offerBranchTimes[branch].days) {

                            if (day.day_code == "sat") {
                                body += `<tr>
                                        <td>السبت</td>
                                        <td><input type="time" name="branchTimes[${branch}][days][sat][from]" value="${day.start_from}"></td>
                                        <td><input type="time" name="branchTimes[${branch}][days][sat][to]" value="${day.end_to}"></td>
                                      </tr>`;
                            }

                            if (day.day_code == "sun") {
                                body += `<tr>
                                        <td>الأحد</td>
                                        <td><input type="time" name="branchTimes[${branch}][days][sun][from]" value="${day.start_from}"></td>
                                        <td><input type="time" name="branchTimes[${branch}][days][sun][to]" value="${day.end_to}"></td>
                                      </tr>`;
                            }
                            if (day.day_code == "mon") {
                                body += `<tr>
                                        <td>الاثنين</td>
                                        <td><input type="time" name="branchTimes[${branch}][days][mon][from]" value="${day.start_from}"></td>
                                        <td><input type="time" name="branchTimes[${branch}][days][mon][to]" value="${day.end_to}"></td>
                                      </tr>`;
                            }
                            if (day.day_code == "tue") {
                                body += `<tr>
                                        <td>الثلاثاء</td>
                                        <td><input type="time" name="branchTimes[${branch}][days][tue][from]" value="${day.start_from}"></td>
                                        <td><input type="time" name="branchTimes[${branch}][days][tue][to]" value="${day.end_to}"></td>
                                      </tr>`;
                            }
                            if (day.day_code == "wed") {
                                body += `<tr>
                                        <td>الأربعاء</td>
                                        <td><input type="time" name="branchTimes[${branch}][days][wed][from]" value="${day.start_from}"></td>
                                        <td><input type="time" name="branchTimes[${branch}][days][wed][to]" value="${day.end_to}"></td>
                                      </tr>`;
                            }
                            if (day.day_code == "thu") {
                                body += `<tr>
                                        <td>الخميس</td>
                                        <td><input type="time" name="branchTimes[${branch}][days][thu][from]" value="${day.start_from}"></td>
                                        <td><input type="time" name="branchTimes[${branch}][days][thu][to]" value="${day.end_to}"></td>
                                      </tr>`;
                            }
                            if (day.day_code == "fri") {
                                body += `<tr>
                                        <td>الجمعة</td>
                                        <td><input type="time" name="branchTimes[${branch}][days][fri][from]" value="${day.start_from}"></td>
                                        <td><input type="time" name="branchTimes[${branch}][days][fri][to]" value="${day.end_to}"></td>
                                      </tr>`;
                            }
                        }

                        body += `</tbody>
                                  </table>
                        </div>`;
                        allBranchTimes.append(body);
                    }
                }
            })
            ;

        })
        ;

        $(document).ready(function () {
            $('.js-example-basic-single').select2();
            $('.js-example-basic-multiple').select2();
        });

        $(document).on('click', '#payment_method', function (e) {

            if ($(this).val() == 6) {
                if ($(this).prop("checked") == true) { // Checkbox is checked
                    $('#amountTypeDiv').show();
                    $('#payment_amount_type').val('all');
                } else if ($(this).prop("checked") == false) { // Checkbox is unchecked
                    $('#amountTypeDiv').hide();
                    $('#customAmountDiv').hide();
                }
            }

        });

        $(document).on('change', '#payment_amount_type', function (e) {
            $(this).val() == 'custom' ? $('#customAmountDiv').show() : $('#customAmountDiv').hide();
        });

        var count = 0;
        $(document).on('click', '.btnAddMoreContent', function () {

            var allContent = $('#allContentDivs');

            var body = `<div style="padding-top: 5px" id="contentBox_${count}"><div class="col-sm-6">
                            <label for="title"> المحتوى بالعربية </label>
                            <input type="text" name="offer_content[ar][]" placeholder="المحتوى بالعربية" style="width: 100%;" value="">
                        </div>
                        <div class="col-sm-6" style="padding-top: 5px">
                            <label for="title"> المحتوى بالإنجليزية </label>
                            <input type="text" name="offer_content[en][]" placeholder="المحتوى بالإنجليزية" style="width: 73%;" value="">
                            <button type="button" class="btnDeleteContent btn btn-danger sm" onclick="deleteContentBox(${count})"><i
                            class="menu-icon fa fa-trash-o fa-fw"></i></button>
                        </div></div>`;
            allContent.append(body);
            count++;
        });

        function deleteContentBox(id) {
            $('#contentBox_' + id).remove();
        }

        $('#branches').on('select2:select', function (e) {
            var data = e.params.data;
            // console.log(data);

            var allBranchTimes = $('#branchTimesDiv');
            var body = `<div id="branchTimeBox_${data.id}" class="form-group has-float-label col-sm-6">
                            <h3># ${data.text} #</h3>
                            <input type="number" name="branchTimes[${data.id}][duration]" placeholder="مدة الإنتظار" style="width: 100%">
                            <table class="table">
                                    <thead>
                                      <tr>
                                        <th>اليوم</th>
                                        <th>من</th>
                                        <th>الى</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td>السبت</td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][sat][from]"></td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][sat][to]"></td>
                                      </tr>
                                      <tr>
                                        <td>الاحد</td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][sun][from]"></td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][sun][to]"></td>
                                      </tr>
                                      <tr>
                                        <td>الاثنين</td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][mon][from]"></td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][mon][to]"></td>
                                      </tr>
                                      <tr>
                                        <td>الثلاثاء</td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][tue][from]"></td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][tue][to]"></td>
                                      </tr>
                                      <tr>
                                        <td>الاربعاء</td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][wed][from]"></td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][wed][to]"></td>
                                      </tr>
                                      <tr>
                                        <td>الخميس</td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][thu][from]"></td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][thu][to]"></td>
                                      </tr>
                                      <tr>
                                        <td>الجمعة</td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][fri][from]"></td>
                                        <td><input type="time" name="branchTimes[${data.id}][days][fri][to]"></td>
                                      </tr>
                                    </tbody>
                                  </table>
                        </div>`;
            allBranchTimes.append(body);
        });

        $('#branches').on('select2:unselect', function (e) {
            var data = e.params.data;
            // console.log(data);
            $('#branchTimeBox_' + data.id).remove();
        });

        $('#parent_categories').on('select2:select', function (e) {
            var data = e.params.data;
            // console.log('data:::show:::', data);

            $.ajax({
                type: 'post',
                url: "{{Route('admin.offers.getChildCatById')}}",
                data: {
                    'id': data.id
                    //'_token'   :   $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (res) {
                    var childCategories = $('#childCategories');
                    var body = `<div id="child-${data.id}" class="form-group has-float-label col-sm-6" style="padding-bottom: 8px;">
                                    <h3># ${data.text}: الاقسام الفرعية </h3>`;
                    for (let childCat of res.childCategories) {
                        body += `<input name="child_category_ids[]" type="checkbox" class="ace" value="${childCat.id}"><span class="lbl"> ${childCat.name_ar} </span>`;
                    }
                    body += `</div>`;
                    childCategories.append(body);
                }
            });

        });
        $('#parent_categories').on('select2:unselect', function (e) {
            var data = e.params.data;
            $('#child-' + data.id).remove();
        });

    </script>
@stop
