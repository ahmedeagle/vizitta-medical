@extends('layouts.master')

@section('title', 'الفروع')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('branch') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-ambulance"></i> الفروع </h1>
        </div>
        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.branch.add') }}">
                <i class="fa fa-plus"></i> إضافة فرع جديد
            </a>
        </div>
    </div>

    <br>
    <div class="row">
        <div class="col-12  d-flex flex-wrap justify-content-center">
            <form class="d-flex flex-wrap" action="{{route('admin.branch')}}" method="GET">
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
        <table id="branches-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم بالعربيه</th>
                <th>الإسم بالإنجليزيه</th>
                <th> اسم المستخدم</th>
                <th>الجوال</th>
                <th> الرصيد</th>
                <th>المدينة</th>
                <th>الحى</th>
                <th>مقدم الخدمة التابع له</th>
                <th>الحالة</th>

                <th>تاريخ الإنضمام</th>
                <th>العمليات</th>
            </tr>
            </thead>
            <tbod>
                @if(isset($branches) &&  $branches -> count() > 0)
                    @foreach($branches as $branch)
                        <tr>
                            <td>{{$branch  -> name_ar}}</td>
                            <td>{{$branch -> name_en}}</td>
                            <td>{{$branch -> username}}</td>
                            <td>{{$branch -> mobile}}</td>
                            <td>{{$branch -> balance}}</td>
                             <td>{{$branch -> city -> name_ar}}</td>
                            <td>{{$branch -> district -> name_ar}}</td>
                            <td>{{$branch -> provider -> name_ar}}</td>
                            <td>{{$branch -> getProviderStatus()}}</td>
                            <td>{{$branch -> created_at}}</td>
                            <td>
                                @include('branch.actions')
                            </td>
                        </tr>

                    @endforeach
                @endif
            </tbod>
        </table>
        {!! $branches ->appends(request()->input())->links('pagination.default') !!}
    </div>
</div>

<!-- Modal -->
<div class="modal fade in" id="add_to_featured_Provider_Modal" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><span id="providerName"></span></h4>
            </div>
            <form action="{{Route('admin.branch.addTOFeatured')}}" method="Post">
                {{csrf_field()}}
                <div class="modal-body">
                    <input onkeypress="return event.charCode >= 48" type="number" min="1" class="form-control"
                           name="duration"
                           placeholder="أدخل عدد أيام تثبت العياده "/>
                    <input type="hidden" class="form-control" name="provider_id" id="providerId" value=""
                           placeholder="  "/>

                </div>
                <div class="modal-footer">
                    <button type="button" style="margin-left: 10px;"
                            class="add-btn-list btn btn-danger " data-dismiss="modal"> تراجع
                    </button>
                    <button type="submit" style="margin-left: 10px;"
                            class="add-btn-list btn btn-success confirmAddProviderToFeatured"> اضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('scripts')
    <script>

        $(document).ready(function () {
            $('#branches-table').DataTable(
                {
                    "paging": false,
                    "bInfo": false,
                    "searching": false,
                    "ordering": false
                }
            );
        });



        $(document).on('click', '.add_to_featured_provider', function (e) {
            e.preventDefault();
            $('#providerName').empty().text($(this).attr('data_provider_name'));
            $('#providerId').val($(this).attr('data_provider_id'));
            $('#add_to_featured_Provider_Modal').modal('toggle');
        });

        $(document).on('click', '.remove_from_featured_provider', function (e) {
            e.preventDefault();

            $.ajax({
                type: 'post',
                url: "{{route('admin.branch.removeFromFeatured')}}",
                data: {
                    'provider_id': $(this).attr('data_provider_id'),
                },
                success: function (data) {
                    $('.featured'+data.branchId).removeClass('remove_from_featured_provider').addClass('add_to_featured_provider');
                    $('.featured'+data.branchId).removeClass("btn-warning").addClass('')
                 }, error: function () {
                 }
            });
        });

    </script>
@stop
