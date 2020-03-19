@extends('layouts.master')

@section('title', ' فلتره العروض')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('offers') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-gift"></i> الفلتره الخاصه بالعروض </h1>
        </div>
        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.offers.filters.create') }}">
                <i class="fa fa-plus"></i> إضافة فلتره
            </a>
        </div>
    </div>
    <div class="col-md-12">
        <table id="filters-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>عنوان الفلتر</th>
                <th> العملية</th>
                <th> السعر</th>
                <th> الحالة</th>
                <th>العمليات</th>
            </tr>
            </thead>

            <tbody>
            @if(isset($filters) &&  $filters -> count() > 0)
                @foreach($filters as $filter)
                    <tr>
                        <td>{{$filter -> title_ar}}</td>
                        <td>{{$filter -> getOperation()}}</td>
                        <td>{{$filter -> price}}</td>
                        <td>{{$filter -> getStatus()}}</td>
                        <td>
                            <div class="actions">
                                <div class="col-md-2">
                                    <a class="btn btn-white btn-danger btn-lg"
                                       href="{{ route('admin.offers.filters.delete', $filter->id) }}" title="مسح">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>

                                <div class="col-md-2">
                                    <a class="btn btn-white btn-danger btn-lg"
                                       href="{{ route('admin.offers.filters.edit', $filter->id) }}"
                                       title=" تعديل الفلتر ">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </div>

                            </div>
                        </td>
                    </tr>

                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#filters-table').DataTable();
        });
    </script>
@stop
