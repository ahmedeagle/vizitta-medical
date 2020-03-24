<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        #loading{background:#f4f4f2;height:100%;left:0;margin:auto;position:fixed;z-index:1000;top:0;width:100%;}
        .bokeh{border:0.01em solid rgba(150,150,150,0.1);border-radius:50%;font-size:100px;height:1em;list-style:outside none none;margin:0 auto;position:relative;top:35%;width:1em;z-index:2147483647;}
        .bokeh li{border-radius:50%;height:0.2em;position:absolute;width:0.2em;}
        .bokeh li:nth-child(1){animation:1.13s linear 0s normal none infinite running rota,3.67s ease-in-out 0s alternate none infinite running opa;background:#00c176 none repeat scroll 0 0;left:50%;margin:0 0 0 -0.1em;top:0;transform-origin:50% 250% 0;}
        .bokeh li:nth-child(2){animation:1.86s linear 0s normal none infinite running rota,4.29s ease-in-out 0s alternate none infinite running opa;background:#ff003c none repeat scroll 0 0;margin:-0.1em 0 0;right:0;top:50%;transform-origin:-150% 50% 0;}
        .bokeh li:nth-child(3){animation:1.45s linear 0s normal none infinite running rota,5.12s ease-in-out 0s alternate none infinite running opa;background:#fabe28 none repeat scroll 0 0;bottom:0;left:50%;margin:0 0 0 -0.1em;transform-origin:50% -150% 0;}
        .bokeh li:nth-child(4){animation:1.72s linear 0s normal none infinite running rota,5.25s ease-in-out 0s alternate none infinite running opa;background:#88c100 none repeat scroll 0 0;margin:-0.1em 0 0;top:50%;transform-origin:250% 50% 0;}
        @keyframes opa{12%{opacity:0.8;}
            19.5%{opacity:0.88;}
            37.2%{opacity:0.64;}
            40.5%{opacity:0.52;}
            52.7%{opacity:0.69;}
            60.2%{opacity:0.6;}
            66.6%{opacity:0.52;}
            70%{opacity:0.63;}
            79.9%{opacity:0.6;}
            84.2%{opacity:0.75;}
            91%{opacity:0.87;}
        }
        @keyframes rota{100%{transform:rotate(360deg);}
        }

        .select2-container--default .select2-selection--multiple{
            padding-bottom: 15px;
        }

    </style>
    @include('includes.header')
</head>
<body class="no-skin rtl">
    <div class="hover_popup">
        <span class="helper"></span>
        <div>
            <div class="popupCloseButton">X</div>
            @yield('popup')
        </div>
    </div>
    <div id="loading">
        <ul class="bokeh">
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>
    @include('includes.navbar')
    <div class="main-container ace-save-state" id="main-container">
        @include('includes.sidebar')
        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    @yield('breadcrumbs')
                </div>
                @yield('content')
            </div>
        </div>
        @include('includes.footer')
    </div>
</body>
</html>




