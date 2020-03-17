@extends('layouts.master')

@section('title', 'الاطباء ')

@section('content')
    @section('breadcrumbs')
        {!! Breadcrumbs::render('doctor') !!}
    @stop

    <div class="page-content">
        <div class="col-md-12">
            <div class="col-md-9 page-header">
                <h1><i class="menu-icon fa fa-user-md"></i> الاطباء  </h1>
            </div>
              <div class="col-md-3 top_action top_button">
                <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.doctor.add') }}">
                    <i class="fa fa-plus"></i> إضافة  طبيب
                </a>
            </div>

        </div>
        <div class="col-md-12">
            <table id="dynamic-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
                <thead>
                <tr>
                    <th>الإسم بالعربيه</th>
                    <th>الإسم بالإنجليزيه</th>
                    <th>الجنس</th>
                    <th>اللقب</th>
                    <th>التخصص</th>
                    <th>الجنسية</th>
                    <th>مقدم الخدمة التابع له</th>
                    <th>سعر الكشف</th>
                    <th>الحالة</th>
                    <th>العمليات</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#dynamic-table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                ajax: "{{ route('admin.doctor.data') }}?queryStr={{$queryStr}}",
                columns: [
                    {name: 'name_ar'},
                    {name: 'name_en'},
                    {name: 'gender'},
                    {name: 'nickname.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'specification.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'nationality.name_ar', render: function (data) {
                            return (data === "N/A" ? '' : data);
                        }, orderable: false
                    },
                    {name: 'providerfullname' , orderable: false, searchable: false},
                    {name: 'price'},
                    {name: 'status'},
                    {name: 'action', orderable: false, searchable: false}
                ],
            });
        });
    </script>
@stop
