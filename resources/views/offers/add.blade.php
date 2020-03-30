@extends('layouts.master')

@section('title', 'إضافة عرض')

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
    {!! Breadcrumbs::render('add.offers') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-magic"></i> إضافة عرض </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => 'admin.offers.store', 'class' => 'form' , 'files' => true]) }}
        @include('offers.form', ['btn' => 'حفظ'])
        {{ Form::close() }}
    </div>
</div>
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
            $('.js-example-basic-single').select2();
            $('.js-example-basic-multiple').select2();
        });

    </script>

    <script type="text/javascript">

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

