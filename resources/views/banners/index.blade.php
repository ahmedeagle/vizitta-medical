@extends('layouts.master')

@section('title', 'بنرات العروض')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('banners') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-picture-o"></i> بنرات العروض </h1>
        </div>
        <div class="col-md-3 top_action top_button">
            <a class="btn btn-white btn-info btn-lg btn-bold" href="{{ route('admin.offers.banners.add') }}">
                <i class="fa fa-plus"></i> إضافة بنر عرض جديد
            </a>
        </div>
    </div>

    <br>
    {{--<div class="row">
        <div class="col-12  d-flex flex-wrap justify-content-center">
            <form class="d-flex flex-wrap" action="{{route('admin.branch')}}" method="GET">
                <div class="form-group has-float-label mx-2">
                    <input class="form-control " name="generalQueryStr" value="{{@Request::get('generalQueryStr')}}"
                           placeholder="ابحث باحدي الحقول ">
                </div>
                <div class="form-group has-float-label mx-2" style="display: none;">
                    <button type="submit" class="btn btn-success form-control "><i class="fa fa-search"></i> ابحث
                    </button>
                </div>
            </form>
        </div>
    </div>--}}

    <div class="col-md-12">
        <table id="branches-table" class="table table-striped table-bordered table-hover no-footer" width="100%">
            <thead>
            <tr>
                <th>صوره البانر</th>
                <th>النوع</th>
                <th> موجه الي</th>
                <th>العمليات</th>
            </tr>
            </thead>
            <tbod>
                @if(isset($banners) &&  $banners -> count() > 0)
                    @foreach($banners as $banner)
                        <tr>
                            <td><img style="width: 80px; height: 80px;border-radius:50px;" class="nav-user-photo" src="{{$banner  -> photo}}"></td>
                            <td>
                                @if($banner -> bannerable_type == 'App\Models\OfferCategory')
                                    اقسام
                                @elseif($banner -> bannerable_type === 'App\Models\Offer')
                                    عروض
                                @else
                                    ---
                                @endif

                            </td>
                            <td>
                                @if($banner -> bannerable_type === 'App\Models\OfferCategory')
                                    {{isset($banner -> bannerable -> name_ar)?  @$banner -> bannerable -> name_ar : 'جميع الاقسام'}}
                                @elseif($banner -> bannerable_type === 'App\Models\Offer')
                                    {{@$banner -> bannerable -> title_ar }}
                                @else
                                    ---
                                @endif


                            </td>
                            <td>
                                @include('banners.actions')
                            </td>
                        </tr>

                    @endforeach
                @endif
            </tbod>
        </table>
        {!! $banners ->appends(request()->input())->links('pagination.default') !!}
    </div>
</div>


@stop

