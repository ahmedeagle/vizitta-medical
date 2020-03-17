@extends('layouts.master')

@section('title', 'المستخدمين')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('user') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-users"></i> المستخدمين </h1>
        </div>
    </div>

    <br>
    <div class="row">
        <div class="col-12  d-flex flex-wrap justify-content-center">
            <form class="d-flex flex-wrap" action="{{route('admin.user')}}" method="GET">
                <div class="form-group has-float-label mx-2">
                    <input class="form-control " name="generalQueryStr" value="{{@Request::get('generalQueryStr')}}" placeholder="ابحث باحدي الحقول ">
                </div>
                <div class="form-group has-float-label mx-2" style="display: none;">
                    <button type="submit" class="btn btn-success form-control "><i class="fa fa-search"></i> ابحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-12">
        <table id="users-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم</th>
                <th>الجوال</th>
                <th>رقم الهوية</th>
                <th> النقاط المكتسبة</th>
                <th>تاريخ الميلاد</th>
                <th>المدينة</th>
                <th>شركة التأمين</th>
                <th>الحالة</th>
                <th>تاريخ الإنضمام</th>
                <th>العمليات</th>
            </tr>
            </thead>

            <tbody>
            @if(isset($users) &&  $users -> count() > 0)
                @foreach($users as $user)
                    <tr>
                        <td>{{$user  -> name}}</td>
                        <td>{{$user -> mobile}}</td>
                        <td>{{$user -> id_number}}</td>
                        <td>{{isset($user -> point  -> points) ? $user -> point  -> points : 0}}</td>
                        <td>{{$user -> birth_date}}</td>
                        <td>{{$user -> city -> name_ar}}</td>
                        <td>{{$user -> insuranceCompany -> name_ar }}</td>
                        <td>{{$user -> getUserStatus() }}</td>
                        <td>{{$user -> created_at}}</td>
                        <td>
                            <div class="actions">
                                <div class="col-md-2">
                                    <a class="btn btn-white btn-primary btn-lg"
                                       href="{{ route('admin.user.view', $user->id) }}" title="التفاصيل">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </div>

                                @if($user->status)
                                    <div class="col-md-2">
                                        <a class="btn btn-white btn-warning btn-lg"
                                           href="{{ route('admin.user.status', [$user->id, 0]) }}" title="إلغاء تفعيل">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    </div>
                                @else
                                    <div class="col-md-2">
                                        <a class="btn btn-white btn-success btn-lg"
                                           href="{{ route('admin.user.status', [$user->id, 1]) }}" title="تفعيل">
                                            <i class="fa fa-check"></i>
                                        </a>
                                    </div>
                                @endif

                                @if(count($user->reservations) == 0)
                                    <div class="col-md-2">
                                        <a class="btn btn-white btn-danger btn-lg"
                                           href="{{ route('admin.user.delete', $user->id) }}" title="مسح">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                @endif
                            </div>

                        </td>
                    </tr>

                @endforeach
            @endif
            </tbody>
        </table>
        {!! $users ->appends(request()->input())->links('pagination.default') !!}
    </div>
</div>
@stop

@section('scripts')
    <script>

        $(document).ready(function () {
            $('#users-table').DataTable(
                {
                    "paging": false,
                    "bInfo" : false,
                    "searching": false,
                    "ordering": false
                }
            );
        });
    </script>


@stop
