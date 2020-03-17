<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>{{ trans('messages.Health Station') }}</title>
    <style type="text/css">
        #outlook a {
            padding: 0;
        }
        h1, h2, h3, h4, h5{
            text-align: right !important;
        }
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
        }
        body {
            margin: 0;
            padding: 0;
        }
        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        table {
            border-collapse: collapse !important;
        }
        body, #bodyTable, #bodyCell {
            height: 100% !important;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }
        #bodyCell {
            padding: 20px;
        }
        #templateContainer {
            width: 600px;
        }
        body, #bodyTable {
            background-color: #DEE0E2;
        }
        #bodyCell {
            border-top: 4px solid #BBBBBB;
        }
        #templateContainer {
            border: 1px solid #BBBBBB;
        }
        h1 {
            color: #202020 !important;
            display: block;
            font-family: 'Droid Arabic Kufi', sans-serif;
            font-size: 26px;
            font-style: normal;
            font-weight: bold;
            line-height: 100%;
            letter-spacing: normal;
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 10px;
            margin-left: 0;
            text-align: left;
        }
        h2 {
            color: #404040 !important;
            display: block;
            font-family: 'Droid Arabic Kufi', sans-serif;
            font-size: 20px;
            font-style: normal;
            font-weight: bold;
            line-height: 100%;
            letter-spacing: normal;
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 10px;
            margin-left: 0;
            text-align: left;
        }
        h3 {
            color: #606060 !important;
            display: block;
            font-family: 'Droid Arabic Kufi', sans-serif;
            font-size: 16px;
            font-style: italic;
            font-weight: normal;
            line-height: 100%;
            letter-spacing: normal;
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 10px;
            margin-left: 0;
            text-align: left;
        }
        h4 {
            color: #808080 !important;
            display: block;
            font-family: 'Droid Arabic Kufi', sans-serif;
            font-size: 14px;
            font-style: italic;
            font-weight: normal;
            line-height: 100%;
            letter-spacing: normal;
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 10px;
            margin-left: 0;
            text-align: left;
        }
        .preheaderContent a:link, .preheaderContent a:visited, /* Yahoo! Mail Override */
        .preheaderContent a .yshortcuts /* Yahoo! Mail Override */
        {
            color: #606060;
            font-weight: normal;
            text-decoration: underline;
        }
        #templateHeader {
            background-color: #F4F4F4;
            border-top: 1px solid #FFFFFF;
            border-bottom: 1px solid #CCCCCC;
        }
        .headerContent {
            color: #505050;
            font-family: 'Droid Arabic Kufi', sans-serif;
            font-size: 20px;
            font-weight: bold;
            line-height: 100%;
            padding-top: 0;
            padding-right: 0;
            padding-bottom: 0;
            padding-left: 0;
            text-align: left;
            vertical-align: middle;
        }
        .headerContent a:link, .headerContent a:visited, /* Yahoo! Mail Override */
        .headerContent a .yshortcuts /* Yahoo! Mail Override */
        {
            color: #EB4102;
            font-weight: normal;
            text-decoration: underline;
        }
        #headerImage {
            height: auto;
            max-width: 600px;
        }
        #templateBody {
            background-color: #F4F4F4;
            border-top: 1px solid #FFFFFF;
            border-bottom: 1px solid #CCCCCC;
        }
        .bodyContent {
            color: #505050;
            font-family: 'Droid Arabic Kufi', sans-serif;
            font-size: 14px;
            line-height: 150%;
            padding-top: 20px;
            padding-right: 20px;
            padding-bottom: 20px;
            padding-left: 20px;
            text-align: left;
            background: #f4f4f4;
        }
        .bodyContent a:link, .bodyContent a:visited, /* Yahoo! Mail Override */
        .bodyContent a .yshortcuts /* Yahoo! Mail Override */
        {
            color: #EB4102;
            font-weight: normal;
            text-decoration: underline;
        }
        .bodyContent img {
            display: inline;
            height: auto;
            max-width: 560px;
        }
        #templateFooter {
            background-color: #F4F4F4;
            border-top: 1px solid #FFFFFF;
        }
        .footerContent {
            color: #808080;
            font-family: 'Droid Arabic Kufi', sans-serif;
            font-size: 10px;
            line-height: 150%;
            padding-top: 20px;
            padding-right: 20px;
            padding-bottom: 20px;
            padding-left: 20px;
            text-align: left;
        }
        .footerContent a:link, .footerContent a:visited, /* Yahoo! Mail Override */
        .footerContent a .yshortcuts, .footerContent a span /* Yahoo! Mail Override */
        {
            color: #606060;
            font-weight: normal;
            text-decoration: underline;
        }
        @media only screen and (max-width: 480px) {
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: none !important;
            }
            body {
                width: 100% !important;
                min-width: 100% !important;
            }
            #bodyCell {
                padding: 10px !important;
            }
            #templateContainer {
                max-width: 600px !important;
                width: 100% !important;
            }
            h1 {
                font-size: 24px !important;
                line-height: 100% !important;
            }
            h2 {
                font-size: 20px !important;
                line-height: 100% !important;
            }
            h3 {
                font-size: 18px !important;
                line-height: 100% !important;
            }
            h4 {
                font-size: 16px !important;
                line-height: 100% !important;
            }
            #headerImage {
                height: auto !important;
                max-width: 600px !important;
                width: 100% !important;
            }
            .headerContent {
                font-size: 20px !important;
                line-height: 125% !important;
            }
            .bodyContent {
                font-size: 18px !important;
                line-height: 125% !important;
            }
            .footerContent {
                font-size: 14px !important;
                line-height: 115% !important;
            }
            .footerContent a {
                display: block !important;
            }
        }
        tr ul{text-align: right;}
        tr li{list-style: none;}
    </style>
</head>
<body style="text-align:right;direction:rtl;font-family:'Droid Arabic Kufi', sans-serif;" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
<center>
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" id="bodyTable">
        <tr>
            <td align="center" valign="top" id="bodyCell">
                <!-- BEGIN TEMPLATE // -->
                <table border="0" cellpadding="0" cellspacing="0" id="templateContainer">
                    <tr>
                        <td align="center" valign="top">
                            <!-- BEGIN HEADER // -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="templateHeader">
                                <tr>
                                    <td valign="top" class="headerContent">
                                        <img src="{{asset('images/banner-bg.jpg')}}"
                                             style="max-width:600px;" id="headerImage" mc:label="header_image"
                                             mc:edit="header_image" mc:allowdesigner mc:allowtext/>
                                    </td>
                                </tr>
                            </table>
                            <!-- // END HEADER -->
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <!-- BEGIN BODY // -->
                            <table border="0" cellpadding="0" cellspacing="0" width="600px;" id="templateBody">
                                <tr>
                                    <td valign="top" class="bodyContent" mc:edit="body_content">
                                        <h3>مرحبا,</h3>
                                        <ul>
                                            @yield('mail_content')
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                            <!-- // END BODY -->
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <!-- BEGIN FOOTER // -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="templateFooter">
                                <tr>
                                    <td valign="top" class="footerContent" mc:edit="footer_content00">
                                        <table>
                                            <tr>
                                                <!-- ****** contact us ******* -->
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <!-- // END FOOTER -->
                        </td>
                    </tr>
                </table>
                <!-- // END TEMPLATE -->
            </td>
        </tr>
    </table>
</center>
</body>
</html>