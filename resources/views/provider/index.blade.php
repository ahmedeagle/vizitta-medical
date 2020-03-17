@extends('layouts.master')

@section('title', ' مقدمى الخدمة')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('provider') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-home"></i> مقدمى الخدمة </h1>
        </div>
        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.provider.add') }}">
                <i class="fa fa-plus"></i> إضافة مقدم خدمة
            </a>
        </div>
    </div>

    <br>
    <div class="row">
        <div class="col-12  d-flex flex-wrap justify-content-center">
            <form class="d-flex flex-wrap" action="{{route('admin.provider')}}" method="GET">
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
        <table id="providers-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>الإسم بالعربيه</th>
                <th>الإسم بالإنجليزيه</th>
                <th>أسم المستخدم</th>
                <th>الجوال</th>
                <th>نسبة التطبيق للكشف%</th>
                <th>نسبة التطبيق للفاتورة%</th>
                <th>الرقم التجارى</th>
                <th>النوع</th>
                <th>المدينة</th>
                <th>الحى</th>
                <th>الحالة</th>
                <th>سحوبات</th>
                <th>تاريخ الإنضمام</th>
                <th>العمليات</th>
            </tr>
            </thead>
            <tbody>
            @if(isset($providers) &&  $providers -> count() > 0)
                @foreach($providers as $provider)
                    <tr>
                        <td>{{$provider  -> name_ar}}</td>
                        <td>{{$provider -> name_en}}</td>
                        <td>{{$provider -> username}}</td>
                        <td>{{$provider -> mobile}}</td>
                        <td>{{$provider -> application_percentage}}</td>
                        <td>{{$provider -> application_percentage_bill}}</td>
                        <td>{{$provider -> commercial_no}}</td>
                        <td>{{$provider -> type -> name_ar}}</td>
                        <td>{{$provider -> city -> name_ar}}</td>
                        <td>{{$provider -> district -> name_ar}}</td>

                        <td>{{$provider -> getProviderStatus()}}</td>
                        <td>{{$provider -> getProviderLottery() }}</td>
                        <td>{{$provider -> created_at}}</td>
                        <td>
                            @include('provider.actions')
                        </td>
                    </tr>

                @endforeach
            @endif
            </tbody>
        </table>
        {!! $providers ->appends(request()->input())->links('pagination.default') !!}
    </div>
</div>
@stop

@section('scripts')
    <script>


        $(document).ready(function () {
            $('#providers-table').DataTable(
                {
                    "paging": false,
                    "bInfo": false,
                    "searching": false,
                    "ordering": false
                }
            );
        });

        $(document).on('click', '.add_to_lottery', function (e) {

            $.ajax({
                type: 'post',
                url: "{{route('admin.lotteriesBranches.add')}}",
                data: {
                    'provider_id': $(this).attr('data_provider_id'),
                },
                success: function (data) {
                    $('.lottery' + data.branchId).removeClass('add_to_lottery').addClass('remove_from_lottery');
                    $('.lottery' + data.branchId).removeClass("btn-default").addClass('btn-warning')
                }, error: function () {
                }
            });
        });

        $(document).on('click', '.remove_from_lottery', function (e) {
            e.preventDefault();

            $.ajax({
                type: 'post',
                url: "{{route('admin.lotteriesBranches.remove')}}",
                data: {
                    'provider_id': $(this).attr('data_provider_id'),
                },
                success: function (data) {
                    $('.lottery' + data.branchId).removeClass('remove_from_lottery').addClass('add_to_lottery');
                    $('.lottery' + data.branchId).removeClass("btn-warning").addClass('')
                }, error: function () {
                }
            });
        });


    </script>
@stop
