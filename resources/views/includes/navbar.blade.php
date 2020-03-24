<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state w-100" id="navbar-container">


        <div class="navbar-header pull-right">
            <a href="index.html" class="navbar-brand">
                <small>
                    Medical Call
                </small>
            </a>
        </div>
        <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
            <span class="sr-only">Toggle sidebar</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <div class="navbar-buttons navbar-header pull-left" role="navigation">
            <ul class="nav ace-nav">
                <li class="purple dropdown-notifications">
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <i class="ace-icon fa fa-bell icon-animated-bell"></i>
                        <span class="badge badge-important notif-count" data-count="8">8</span>
                    </a>

                    <ul class="dropdown-menu-left dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close">
                        <li class="dropdown-header">
                            <i class="ace-icon fa fa-exclamation-triangle"></i>
                            8 Notifications
                        </li>

                        <li class="dropdown-content scrollable-container">
                            <ul class="dropdown-menu dropdown-navbar navbar-pink">
                                @if(takeLastNotifications(5))
                                    @foreach(takeLastNotifications(5) as $notify)
                                        <li>
                                            <a href="#">
                                                <div class="clearfix">
													<span class="pull-left">
														<i class="btn btn-xs no-hover btn-pink fa fa-comment"></i>
														New Comments
													</span>
                                                    <div class="media-left">
                                                   <span class="avatar avatar-sm avatar-online rounded-circle">
                                                   <img src="{{$notify -> notificationable -> photo}}"
                                                   alt="avatar"><i></i></span>
                                                    </div>

                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </li>

                        <li class="dropdown-footer">
                            <a href="#">
                                See all notifications
                                <i class="ace-icon fa fa-arrow-right"></i>
                            </a>
                        </li>
                    </ul>
                </li>
                </li>


                <li class="purple">
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <i class="ace-icon fa fa-envelope-o icon-animated-bell"></i>
                        <span class="badge badge-important">8</span>
                    </a>

                    <ul class="dropdown-menu-left dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close">
                        <li class="dropdown-header">
                            <i class="ace-icon fa fa-exclamation-triangle"></i>
                            8 Notifications
                        </li>

                        <li class="dropdown-content">
                            <ul class="dropdown-menu dropdown-navbar navbar-pink">
                                <li>
                                    <a href="#">
                                        <div class="clearfix">
													<span class="pull-left">
														<i class="btn btn-xs no-hover btn-pink fa fa-comment"></i>
														New Comments
													</span>
                                            <span class="pull-right badge badge-info">+12</span>
                                        </div>
                                    </a>
                                </li>

                                <li>
                                    <a href="#">
                                        <i class="btn btn-xs btn-primary fa fa-user"></i>
                                        Bob just signed up as an editor ...
                                    </a>
                                </li>

                                <li>
                                    <a href="#">
                                        <div class="clearfix">
													<span class="pull-left">
														<i class="btn btn-xs no-hover btn-success fa fa-shopping-cart"></i>
														New Orders
													</span>
                                            <span class="pull-right badge badge-success">+8</span>
                                        </div>
                                    </a>
                                </li>

                                <li>
                                    <a href="#">
                                        <div class="clearfix">
													<span class="pull-left">
														<i class="btn btn-xs no-hover btn-info fa fa-twitter"></i>
														Followers
													</span>
                                            <span class="pull-right badge badge-info">+11</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="dropdown-footer">
                            <a href="#">
                                See all notifications
                                <i class="ace-icon fa fa-arrow-right"></i>
                            </a>
                        </li>
                    </ul>
                </li>
                </li>


                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="{{ asset("images/male.png") }}" alt="Admin"/>
                        <span class="user-info">
							<small>مرحبا,</small>
							@if(Auth::user()){{ Auth::user()->name_ar }}@endif
                        </span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-left dropdown-menu dropdown-blue dropdown-caret dropdown-close">
                        <li>
                            <a href="{{ route('admin.data.information.edit') }}">
                                <i class="ace-icon fa fa-user"></i>
                                تعديل الملف الشخصى
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('Logout') }}">
                                <i class="ace-icon fa fa-power-off"></i>
                                تسجيل الخروج
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>


        </div>
    </div>
</div>
