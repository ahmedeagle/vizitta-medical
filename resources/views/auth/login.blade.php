<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Login</title>
    {!! Html::style('css/bootstrap.min.css') !!}
    {!! Html::style('css/font-awesome.min.css') !!}
    {!! Html::style('css/fonts.googleapis.com.css') !!}
    {!! Html::style('css/ace-rtl.min.css') !!}
    {!! Html::style('css/ace.min.css') !!}

    {!! Html::script('js/html5shiv.min.js') !!}
    {!! Html::script('js/respond.min.js') !!}
</head>
<body class="login-layout light-login rtl">
<div class="main-container">
    <div class="main-content">
        <div class="row">
            <div class="col-sm-10 col-sm-offset-1">
                <div class="login-container">
                    <div class="center">
                        <h1>
                            <img src="{{ asset('images/logo.png') }}" alt="Medical Call">
                            <span class="red">Medical</span>
                            <span class="grey" id="id-text2">Call</span>
                        </h1>
                    </div>
                    <div class="space-6"></div>

                    <div class="position-relative">
                        <div id="login-box" class="login-box visible widget-box no-border"#forgot-box>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <h4 class="header health lighter bigger">
                                        <i class="ace-icon fa fa-sign-in"></i> تسجيل الدخول
                                    </h4>
                                    <div class="space-6"></div>
                                    <form class="form-horizontal" role="form" method="POST" action="{{ route('login') }}">
                                        {{ csrf_field() }}
                                        <fieldset>
                                            <label class="block clearfix">
                                                <span class="block input-icon input-icon-right form-group {{ $errors->has('email') ? ' has-error' : '' }}">
                                                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="البريد الإلكترونى" required autofocus />
                                                    <i class="ace-icon fa fa-envelope"></i>
                                                </span>
                                            </label>

                                            <label class="block clearfix">
                                                <span class="block input-icon input-icon-right form-group {{ $errors->has('email') ? ' has-error' : '' }}">
                                                    <input id="password" type="password" name="password" class="form-control" placeholder="كلمة المرور" required />
                                                    <i class="ace-icon fa fa-lock"></i>
                                                </span>
                                            </label>
                                            @if ($errors->has('email'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                            @endif

                                            <div class="space"></div>
                                            <div class="clearfix">
                                                <button type="submit" class="pull-right btn btn-sm">
                                                    <i class="ace-icon fa fa-key"></i>
                                                    <span class="bigger-110">دخول</span>
                                                </button>
                                            </div>
                                            <div class="space-4"></div>
                                        </fieldset>
                                    </form>
                                </div>
                                <div class="toolbar clearfix">
                                    <div style="display: none;">
                                        <a href="#" data-target="#forgot-box" class="forgot-password-link">
                                            <i class="ace-icon fa fa-arrow-left"></i>
                                            نسيت كلمة المرور
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="forgot-box" class="forgot-box widget-box no-border">
                            <div class="widget-body">
                                <div class="widget-main">
                                    <h4 class="header health lighter bigger">
                                        <i class="ace-icon fa fa-key"></i>
                                        إعادة كلمة المرور
                                    </h4>
                                    <div class="space-6"></div>
                                    <p>
                                        إدخل البريد الإكترونى حتى تستلم التعليمات
                                    </p>
                                    <form method="POST" action="{{ route('password.update') }}">
                                        <fieldset>
                                            <label class="block clearfix">
                                                <span class="block input-icon input-icon-right form-group">
                                                    <input type="email" name="email" class="form-control" placeholder="البريد الإلكترونى">
                                                    <i class="ace-icon fa fa-envelope"></i>
                                                </span>
                                            </label>
                                            <div class="clearfix">
                                                <button type="button" class="width-35 pull-right btn btn-sm">
                                                    <i class="ace-icon fa fa-lightbulb-o"></i>
                                                    <span class="bigger-110">إرسل!</span>
                                                </button>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                                <div class="toolbar center">
                                    <a href="#" data-target="#login-box" class="back-to-login-link">
                                        الرجوع إلى تسجيل الدخول
                                        <i class="ace-icon fa fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{!! Html::script('js/jquery-2.1.4.min.js') !!}
<script type="text/javascript">
    if('ontouchstart' in document.documentElement) document.write("<script src='js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
</script>
<!-- inline scripts related to this page -->
<script type="text/javascript">
    jQuery(function($) {
        $(document).on('click', '.toolbar a[data-target]', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            $('.widget-box.visible').removeClass('visible');//hide others
            $(target).addClass('visible');//show target
        });
    });
    //you don't need this, just used for changing background
    jQuery(function($) {
        $('#btn-login-dark').on('click', function(e) {
            $('body').attr('class', 'login-layout');
            $('#id-text2').attr('class', 'white');
            $('#id-company-text').attr('class', 'blue');

            e.preventDefault();
        });
        $('#btn-login-light').on('click', function(e) {
            $('body').attr('class', 'login-layout light-login');
            $('#id-text2').attr('class', 'grey');
            $('#id-company-text').attr('class', 'blue');

            e.preventDefault();
        });
        $('#btn-login-blur').on('click', function(e) {
            $('body').attr('class', 'login-layout blur-login');
            $('#id-text2').attr('class', 'white');
            $('#id-company-text').attr('class', 'light-blue');

            e.preventDefault();
        });
    });
</script>
</body>
</html>
