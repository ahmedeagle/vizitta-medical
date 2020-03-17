<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state w-100" id="navbar-container">
        <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
            <span class="sr-only">Toggle sidebar</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="{{ asset("images/male.png") }}" alt="Admin" />
                        <span class="user-info">
							<small>مرحبا,</small>
							@if(Auth::user()){{ Auth::user()->name_ar }}@endif
                        </span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-blue dropdown-caret dropdown-close">
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
