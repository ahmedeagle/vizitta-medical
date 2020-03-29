@extends('layouts.master')

@section('title', 'أضافة بانر عرض  ')

@section('styles')

    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet"/>
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('banners.create') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-magic"></i> أضافه بانر عرض </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => ['admin.offers.banners.save'],'method' => 'POST', 'class' => 'form','files' => true] ) }}
        <div class="col-sm-12">
            <div class="row">
                <div class="form-group has-float-label col-sm-6">
                    {{ Form::file('photo', ['placeholder' => 'الصورة', 'class' => 'form-control ' . ($errors->has('photo') ? 'redborder' : '') ]) }}
                    <label for="photo">الصورة</label>
                    <small class="text-danger">{{ $errors->has('photo') ? $errors->first('photo') : '' }}</small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="radio-inline" style="user-select: none">
                        <input style="margin: -8px -19px 0 0 " type="radio" value="App\Models\OfferCategory" name="type"
                               id="category">
                        أقسام
                    </label>
                    <small class="text-danger">{{ $errors->has('type') ? $errors->first('type') : '' }}</small>
                </div>
                <div class="col-md-6">
                    <label class="radio-inline" style="user-select: none">
                        <input style="margin: -8px -19px 0 0 " type="radio" value="App\Models\Offer" name="type"
                               id="offer"
                            {{  old('offers') == 'App\Models\Offer' ? ' checked' : '' }} >
                        عروض
                    </label>
                </div>
            </div>
            <small class="text-danger"></small>
        </div>
        <br>

        <div class="form-group has-float-label col-sm-12" style="display: none" id="category_select">
            <select name="category_id" data-live-search="true" id="mainCategory"
                    class='form-control js-example-basic-single ' {{$errors->has('category_id') ? 'redborder' : ''}}>
                @if(isset($categories) && $categories -> count() > 0)
                    <option value="0">
                        شاشه كل الاقسام
                    </option>
                    @foreach($categories as $category)
                        <option value="{{$category -> id}}">
                            {{$category -> name_ar}}
                        </option>
                    @endforeach
                @endif
            </select>
            <label for="provider_id"> اختر قسم <span class="astric">*</span> </label>
            <small class="text-danger">{{ $errors->has('category_id') ? $errors->first('category_id') : '' }}</small>
        </div>

        <div class="form-group has-float-label col-sm-12 " style="display: none;" id="subCategory_container">
            <select name="subcategory_id"  id="subCategory"
                    class='appendsubcategories form-control ' {{$errors->has('subcategory_id') ? 'redborder' : ''}}>
            </select>
            <label for="branches"> الاقسام الفرعية </label>
            <small
                class="text-danger">{{ $errors->has('subcategory_id') ? $errors->first('subcategory_id') : '' }}</small>
        </div>


        <div class="form-group has-float-label col-sm-12" style="display: none" id="offer_select">
            <select name="offer_id" class='form-control select-single ' {{$errors->has('offer_id') ? 'redborder' : ''}}>
                @if(isset($offers) && $offers -> count() > 0)
                    @foreach($offers as $offer)
                        <option value="{{$offer -> id}}">
                            {{$offer -> title_ar}}
                        </option>
                    @endforeach
                @endif
            </select>
            <label for="provider_id"> أختر عرض </label>
            <small class="text-danger">{{ $errors->has('offer_id') ? $errors->first('offer_id') : '' }}</small>
        </div>


        <div class="form-group col-sm-12 submit">
            {{ Form::submit('حفظ', ['class' => 'btn btn-sm' ]) }}
        </div>
        {{ Form::close() }}
    </div>
</div>

@stop


@section('scripts')

    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            if ($('#category').is(":checked")) {
                $('#category_select').show();
                $('#subCategory_container').show();
                $('#offer_select').hide();
            }
            if ($('#offer').is(":checked")) {
                $('#offer_select').show();
                $('#category_select').hide();
                $('#subCategory_container').hide();
            }
        });
        $(document).on('click', '#category', function () {
            if ($('#category').is(":checked")) {
                $('#category_select').show();
                $('#subCategory_container').show();
                $('#offer_select').hide();
            }
        });

        $(document).on('click', '#offer', function () {

            if ($('#offer').is(":checked")) {
                $('#offer_select').show();
                $('#category_select').hide();
                $('#subCategory_container').hide();

            }
        });

        //get provider branches
        $(document).on('change', '#mainCategory', function (e) {
            e.preventDefault();
            alert($(this).val());
            $.ajax({
                type: 'post',
                url: "{{Route('admin.offerCategories.subcategories')}}",
                data: {
                    'parent_id': $(this).val(),
                    //'_token'   :   $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (data) {
                    $('#subCategory_container').show();
                    $('.appendsubcategories').empty().append(data.subcategories);
                }
            });
        });
    </script>
@stop
