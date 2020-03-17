@extends('layouts.master')

@section('title', 'الهدايا المتاحه بالفرع')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('lotteries') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-gift"></i> هدايا الفرع - {{$branch_name}}</h1>
        </div>
    </div>
    <div class="col-md-12">
        <table id="giftstable" class="table table-striped table-bordered" style="width:100%">
            <thead>
            <tr>
                <th>العنوان</th>
                <th> الكمية الحالية</th>
                <th> الاجراء</th>

            </tr>
            </thead>
            <tbody>
            @if(isset($gifts) && $gifts -> count() > 0)
                @foreach($gifts as $gift)
                    <tr>
                        <td>{{$gift -> title}}</td>
                        <td>{{$gift -> amount}}</td>
                        <td><a class="btn btn-white btn-danger btn-lg"
                               href="{{ route('admin.lotteriesBranches.deleteGift', $gift->id) }}" title="حذف">
                                <i class="fa fa-trash"></i>
                            </a></td>
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
            $('#giftstable').DataTable();
        });
    </script>

@stop
