@extends('layouts.master')

@section('title', ' أقسام الكوبونات ')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('promoCategories') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-6 page-header">
            <h1><i class="menu-icon fa fa-list-ul"></i> أقسام الكوبونات </h1>
        </div>
        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.promoCategories.add') }}">
                <i class="fa fa-plus"></i> إضافة قسم جديد
            </a>
        </div>

        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.promoCategories.reorder') }}">
                ترتيب الاقسام
            </a>
        </div>
    </div>
    <div class="col-md-12">
        <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم بالعربيه</th>
                <th>الإسم بالإنجليزيه</th>
                <th>حالة القسم</th>
                <th>خاصية العد التنازلي </th>
                <th>العمليات</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>


    @if(isset($categories) && $categories -> count() > 0 )
        @foreach($categories as $category)
            @include('includes.modals.promocodeCategory',['category' => $category])
        @endforeach
    @endif

</div>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#dynamic-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.promoCategories.data') }}",
                columns: [
                    {name: 'name_ar'},
                    {name: 'name_en'},
                    {name: 'status'},
                    {name: 'hastimer'},
                    {name: 'action', orderable: false, searchable: false}
                ],
            });
        });

        $(document).on('click', '.add_to_timer', function (e) {
            e.preventDefault();
            $('#add_to_timer_Modal' + $(this).attr('data_category_id')).modal('toggle');
        });

        @if(Session::has('promoCodeModalId'))
        $("#add_to_timer_Modal{{Session::get('promoCodeModalId')}}").modal('toggle');
        @endif


    </script>
@stop
