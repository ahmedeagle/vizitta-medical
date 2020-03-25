@extends('layouts.master')

@section('title', 'مركز التنبيهات')

@section('styles-after')
    <style>
        img {
            width: 40px;
        }
    </style>
@stop
@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('notifications.center') !!}
@stop
<div class="page-content">
    <!-- /section:settings.box -->

    <div class="row">
        <div class="col-xs-12">
            <!-- PAGE CONTENT BEGINS -->
            <div class="row">
                <div class="col-xs-12">
                    <!-- #section:pages/inbox -->
                    <div class="tabbable">
                        <ul id="inbox-tabs" class="inbox-tabs nav nav-tabs padding-16 tab-size-bigger tab-space-1">
                            <li @if(Request::query('status') == 'all' )class="active"@endif>
                                <a href="{{route('notification.center')}}?status=all">
                                    <span class="bigger-110">الكل ( {{\App\Models\GeneralNotification::count()}})</span>
                                </a>
                            </li>
                            <li @if(Request::query('status') == 'read' )class="active"@endif>
                                <a href="{{route('notification.center')}}?status=read">
                                    <span class="bigger-110"> المقروءه ( {{\App\Models\GeneralNotification::where('seen','1')->count()}})</span>
                                </a>
                            </li>
                            <li @if(Request::query('status') == 'unread' )class="active"@endif>
                                <a href="{{route('notification.center')}}?status=unread">
                                    <span class="bigger-110"> الغير مقروءه ( {{\App\Models\GeneralNotification::where('seen','0')->count()}})</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content no-border no-padding">
                            <div id="all" class="tab-pane in active">
                                <div class="message-container">
                                    <!-- #section:pages/inbox.navbar -->
                                    <div id="id-message-list-navbar" class="message-navbar clearfix">
                                        <div>
                                            <div class="messagebar-item-left">
                                            </div>
                                            <div class="messagebar-item-right">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="message-list-container">
                                        @if(isset($notifications) &&  $notifications -> count() > 0)
                                            @forelse($notifications as $notify)
                                                <a href=" @if($notify -> type == 1 )   {{--new reservation notification--}}
                                                {{route('admin.reservation.view',$notify -> data_id)}}?notification={{Vinkla\Hashids\Facades\Hashids::encode($notify -> id)}}
                                                @elseif($notify -> type == 2){{--user rate reservation--}}
                                                {{route('admin.comments')}}?notification={{Vinkla\Hashids\Facades\Hashids::encode($notify -> id)}}
                                                @elseif($notify -> type == 3 or  $notify -> type == 4) {{--user update reservation Date --}}
                                                {{route('admin.reservation.view',$notify -> data_id)}}?notification={{Vinkla\Hashids\Facades\Hashids::encode($notify -> id)}}
                                                @else # @endif"
                                                >
                                                    <div class="message-list">
                                                        <div class="message-item message-unread">

                                                            <img class="nav-user-photo pull-right"
                                                                 src="{{$notify -> notificationable -> logo}}"
                                                                 alt="photo">

                                                            <span style="color: #585858;"
                                                                  class="time">{{date("Y M d H:i:s", strtotime($notify -> created_at))}}</span>

                                                            <span class="">
                                                            {{$notify -> title_ar}}
                                                            <br>
																		<span style="color: #585858;">
																			{{$notify -> content_ar}}
																		</span>
																	</span>
                                                            <br>
                                                        </div>

                                                    </div>
                                                </a>
                                            @empty
                                                <br><br><br><br><br><br><br><br>
                                                <div class="message-list">
                                                    <div class="message-item message-unread">
                                                        <p style="font-size: 24px"> لا يوجد اي تنبيهات حتي اللحظة</p>
                                                    </div>
                                                </div>
                                            @endforelse
                                        @else
                                            <br><br><br><br><br><br><br><br>
                                            <div class="message-list">
                                                <div class="message-item message-unread d-flex justify-content-center">
                                                    <p style="font-size: 24px"> لا يوجد اي تنبيهات حتي اللحظة</p>
                                                </div>
                                            </div>
                                        @endif

                                    </div>

                                    <!-- /section:pages/inbox.message-footer -->
                                </div>
                            </div>
                        </div><!-- /.tab-content -->
                    </div><!-- /.tabbable -->
                {!! $notifications ->appends(request()->input())->links('pagination.default') !!}
                <!-- /section:pages/inbox -->
                </div><!-- /.col -->
            </div><!-- /.row -->
            <!-- PAGE CONTENT ENDS -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</div><!-- /.page-content -->
@stop

