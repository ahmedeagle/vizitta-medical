@extends('layouts.master')

@section('title', ' مستخدمي  السحب العشوائي ')

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('lotteries') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="col-md-9 page-header">
            <h1><i class="menu-icon fa fa-gift"></i>المستخدمين الفائزين في عمليات السحب </h1>
        </div>
    </div>
    <div class="col-md-12">
        <table id="giftstable" class="table table-striped table-bordered" style="width:100%">
            <thead>
            <tr>
                <th>الاسم</th>
                <th>رقم الهاتف</th>
                <th> الهدية</th>
                <th> مقدم الخدمه</th>
            </tr>
            </thead>
            <tbody>
            @if(isset($users) && $users -> count() > 0)
                @foreach($users as $index =>  $user)
                    <tr>
                         <td >{{$user -> name}}</td>
                        <td>{{$user -> mobile}}</td>
                        <td>{{$user -> gifts -> first() -> title}}</td>
                        <td>{{$user -> gifts -> first() -> provider -> name_ar}}</td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>

        <div class="col-md-12">
            <div class="col-md-9 page-header">
                <h1><i class="menu-icon fa fa-gift"></i>المستخدمين  المتاحين للسحب :  {{$userNotWinUntillNow}} </h1>
            </div>
        </div>


    </div>
</div>


@stop

