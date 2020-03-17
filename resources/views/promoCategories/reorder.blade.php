@extends('layouts.master')

@section('title', ' أقسام الكوبونات ')

@section('styles')
    <link href="{{asset('assets/nestedSortable/nestedSortable.css')}}" rel="stylesheet" type="text/css"/>
@stop
@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('reorder') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-list-ul"></i> أعاده ترتيب أقسام الكوبونات </h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>قم بسحب القسم وافلاته</h5>
        </div>
        <div class="card-block">

            @if(isset($categories) && $categories -> count() > 0)

                <ol class="sortable">
                    @foreach($categories as $category)
                        <li id="list_{{$category -> id }}">
                            <div>
            <span class="disclose"><span></span>
                </span>{{$category -> name_ar }} </div>
                        </li>
                    @endforeach
                </ol>

                <br>
                <button id="toArray" class="btn btn-success ladda-button" data-style="zoom-in"><span
                        class="ladda-label"><i class="fa fa-save"></i> حفظ</span></button>

                <br>
                <div class="col-md-6 col-md-offset-3">
                    <div class="alert alert-danger" id="error" style="display: none; text-align: center;"> عفوا هناك خطا برجاء المحاوله مجداا</div>
                </div>
                <div class="col-md-6 col-md-offset-3">
                    <div class="alert alert-success" id="success" style="display: none; text-align: center"> تم حفظ الترتيب بنجاح</div>
                </div>

            @else
                لا يوجد اي أقسام لترتيبها
            @endif

        </div>
    </div>

</div>
@stop

@section('scripts')
    <script src="{{asset('assets/nestedSortable/jquery.mjs.nestedSortable2.js') }}" type="text/javascript"></script>

    <script>
        $(document).ready(function () {

            // initialize the nested sortable plugin
            $('.sortable').nestedSortable({
                forcePlaceholderSize: true,
                handle: 'div',
                helper: 'clone',
                items: 'li',
                opacity: .6,
                placeholder: 'placeholder',
                revert: 250,
                tabSize: 25,
                tolerance: 'pointer',
                toleranceElement: '> div',
                maxLevels: 3,

                isTree: true,
                expandOnHover: 700,
                startCollapsed: false
            });

            $('.disclose').on('click', function () {
                $(this).closest('li').toggleClass('mjs-nestedSortable-collapsed').toggleClass('mjs-nestedSortable-expanded');
            });

            $('#toArray').click(function (e) {
                $("#error").hide();
                $("#success").hide();

                // get the current tree order
                arraied = $('ol.sortable').nestedSortable('toArray', {startDepthCount: 0});
                // log it

                // send it with POST
                $.ajax({
                    url: '{{ Route('admin.promoCategories.reorder.save') }}',
                    type: 'POST',
                    data: {tree: arraied},
                })
                    .done(function () {
                        console.log("success");
                        $("#success").show();
                        $("#error").hide();
                    })
                    .fail(function () {
                        console.log("error");
                        $("#error").show();
                        $("#success").hide();
                    })
                    .always(function () {
                        console.log("complete");
                    });

            });

            $.ajaxPrefilter(function (options, originalOptions, xhr) {
                var token = $('meta[name="csrf_token"]').attr('content');

                if (token) {
                    return xhr.setRequestHeader('X-XSRF-TOKEN', token);
                }
            });
        });
    </script>
@stop
