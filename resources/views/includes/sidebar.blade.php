<div  class="sidebar responsive ace-save-state">
    <ul class="nav nav-list">

        <li class="{{ Request::is('mc33') ? 'active' : '' }}">
            <a href="{{ route('home') }}">
                <i class="menu-icon fa fa-dashboard fa-fw"></i>
                <span class="menu-text">الرئيسية</span>
            </a>
        </li>

        @can('show_insurance_company')
            <li class="{{ Request::is('mc33/insurance_company*') ? 'active' : '' }}">
                <a href="{{ route('admin.insurance.company') }}">
                    <i class="menu-icon fa fa-home fa-fw"></i>
                    <span class="menu-text">شركات التأمين</span>
                </a>
            </li>
        @endcan

        @can('show_providers')
            <li class="{{ Request::is('mc33/provider*') ? 'active' : '' }}">
                <a href="{{ route('admin.provider') }}">
                    <i class="menu-icon fa fa-building-o fa-fw"></i>
                    <span class="menu-text">مقدمى الخدمة</span>
                </a>
            </li>
        @endcan

        @can('show_providers_types')
        <!--provider Types -->
            <li class="{{ Request::is('mc33/types*') ? 'active' : '' }}">
                <a href="{{ route('admin.types') }}">
                    <i class="menu-icon fa fa-building-o fa-fw"></i>
                    <span class="menu-text"> انواع مقدمى الخدمة</span>
                </a>
            </li>
        @endcan


        @can('show_branches')
            <li class="{{ Request::is('mc33/branch*') ? 'active' : '' }}">
                <a href="{{ route('admin.branch') }}">
                    <i class="menu-icon fa fa-ambulance fa-fw"></i>
                    <span class="menu-text">الفروع</span>
                </a>
            </li>
        @endcan

        @can('show_lotteries_branches')
            <li class="{{ Request::is('mc33/lottery/branches*') ? 'active' : '' }}">
                <a href="{{ route('admin.lotteriesBranches.index') }}">
                    <i class="menu-icon fa fa-gift fa-fw"></i>
                    <span class="menu-text">عيادات السحب العشوائي</span>
                </a>
            </li>
        @endcan

        @can('show_doctors')
            <li class="{{ Request::is('mc33/doctor*') ? 'active' : '' }}">
                <a href="{{ route('admin.doctor') }}">
                    <i class="menu-icon fa fa-user-md fa-fw"></i>
                    <span class="menu-text">الاطباء</span>
                </a>
            </li>
        @endcan

        @can('show_specialists')
            <li class="{{ Request::is('mc33/specification*') ? 'active' : '' }}">
                <a href="{{ route('admin.specification') }}">
                    <i class="menu-icon fa fa-list-ul fa-fw"></i>
                    <span class="menu-text">تخصصات الاطباء</span>
                </a>
            </li>
        @endcan

        @can('show_specialists')
            <li class="{{ Request::is('mc33/nickname*') ? 'active' : '' }}">
                <a href="{{ route('admin.nickname') }}">
                    <i class="menu-icon fa fa-lightbulb-o fa-fw"></i>
                    <span class="menu-text">ألقاب الاطباء</span>
                </a>
            </li>
        @endcan

        @can('show_reservations')
            <li class="{{ Request::is('mc33/reservation*') ? 'active' : '' }}">
                <a href="{{ route('admin.reservation') }}?status=all">
                    <i class="menu-icon fa fa-ticket fa-fw"></i>
                    <span class="menu-text">الحجوزات</span>
                </a>
            </li>
        @endcan

        @can('show_users')
            <li class="{{ Request::is('mc33/users*') ? 'active' : '' }}">
                <a href="{{ route('admin.user') }}">
                    <i class="menu-icon fa fa-users fa-fw"></i>
                    <span class="menu-text">المستخدمين</span>
                </a>
            </li>
        @endcan

        @can('show_admins')
            <li class="{{ Request::is('mc33/admins*') ? 'active' : '' }}">
                <a href="{{ route('admin.admins') }}">
                    <i class="menu-icon fa fa-users fa-fw"></i>
                    <span class="menu-text">مستخدمي لوحة التحكم </span>
                </a>
            </li>
        @endcan

        @can('show_cities')
            <li class="{{ Request::is('mc33/city*') ? 'active' : '' }}">
                <a href="{{ route('admin.city') }}">
                    <i class="menu-icon fa fa-hospital-o fa-fw"></i>
                    <span class="menu-text">المدن</span>
                </a>
            </li>
        @endcan

        @can('show_districts')
            <li class="{{ Request::is('mc33/district*') ? 'active' : '' }}">
                <a href="{{ route('admin.district') }}">
                    <i class="menu-icon fa fa-hospital-o fa-fw"></i>
                    <span class="menu-text">الأحياء</span>
                </a>
            </li>
        @endcan

        @can('show_pages')
            <li class="{{ Request::is('mc33/page*') ? 'active' : '' }}">
                <a href="{{ route('admin.customPage') }}">
                    <i class="menu-icon fa fa-file-o fa-fw"></i>
                    <span class="menu-text">الصفحات الفرعية</span>
                </a>
            </li>
        @endcan

        @can('show_nationalities')
            <li class="{{ Request::is('mc33/nationality*') ? 'active' : '' }}">
                <a href="{{ route('admin.nationality') }}">
                    <i class="menu-icon fa fa-user-secret fa-fw"></i>
                    <span class="menu-text">الجنسيات</span>
                </a>
            </li>
        @endcan

        @can('show_promoCategories')
            <li class="{{ Request::is('mc33/promoCategories*') ? 'active' : '' }}">
                <a href="{{ route('admin.promoCategories') }}">
                    <i class="menu-icon fa fa-gift fa-fw"></i>
                    <span class="menu-text">أقسام الكوبونات  </span>
                </a>
            </li>
        @endcan

        @can('show_coupons')
            <li class="{{ Request::is('mc33/promoCode') ? 'active' : '' }}">
                <a href="{{ route('admin.promoCode') }}">
                    <i class="menu-icon fa fa-gift fa-fw"></i>
                    <span class="menu-text">الرموز الترويجيه (Promo Codes)</span>
                </a>
            </li>
        @endcan



        @can('show_promoCategories')
            <li class="{{ Request::is('mc33/offerCategories*') ? 'active' : '' }}">
                <a href="{{ route('admin.offerCategories') }}">
                    <i class="menu-icon fa fa-gift fa-fw"></i>
                    <span class="menu-text">أقسام العروض</span>
                </a>
            </li>
        @endcan

        @can('show_coupons')
            <li class="{{ Request::is('mc33/offers') ? 'active' : '' }}">
                <a href="{{ route('admin.offers') }}">
                    <i class="menu-icon fa fa-gift fa-fw"></i>
                    <span class="menu-text">العروض</span>
                </a>
            </li>
        @endcan

        @can('show_coupons')
            <li class="{{ Request::is('mc33/offers/filters*') ? 'active' : '' }}">
                <a href="{{ route('admin.offers.filters') }}">
                    <i class="menu-icon fa fa-filter fa-fw"></i>
                    <span class="menu-text">فلترة العروض</span>
                </a>
            </li>
        @endcan




        {{--@can('show_coupons')
            <li class="{{ Request::is('mc33/promoCode/filters*') ? 'active' : '' }}">
                <a href="{{ route('admin.promoCode.filters') }}">
                    <i class="menu-icon fa fa-filter fa-fw"></i>
                    <span class="menu-text">فلتره العروض</span>
                </a>
            </li>
        @endcan--}}

        @can('show_coupons')
            <li class="{{ Request::is('mc33/offers/banners*') ? 'active' : '' }}">
                <a href="{{ route('admin.offers.banners') }}">
                    <i class="menu-icon fa fa-picture-o fa-fw"></i>
                    <span class="menu-text"> بنرات العروض </span>
                </a>
            </li>
        @endcan

        <li class="{{ Request::is('mc33/offers/mainbanners*') ? 'active' : '' }}">
            <a href="{{ route('admin.offers.mainbanners') }}">
                <i class="menu-icon fa fa-picture-o fa-fw"></i>
                <span class="menu-text"> بنرات العروض للرئيسي </span>
            </a>
        </li>

        @can('show_provider_messages')
            <li class="{{ Request::is('mc33/provider/message*') ? 'active' : '' }}">
                <a href="{{ route('admin.provider.message') }}">
                    <i class="menu-icon fa fa-envelope-o fa-fw"></i>
                    <span class="menu-text">رسائل مقدمى الخدمات</span>
                </a>
            </li>
        @endcan

        @can('show_user_messages')
            <li class="{{ Request::is('mc33/user/message*') ? 'active' : '' }}">
                <a href="{{ route('admin.user.message') }}">
                    <i class="menu-icon fa fa-envelope-o fa-fw"></i>
                    <span class="menu-text">رسائل المستخدمين</span>
                </a>
            </li>
        @endcan

        @can('show_providers_notifications')
            <li class="{{ Request::is('mc33/notifications/list/providers*') ? 'active' : '' }}">
                <a href="{{ route('admin.notifications','providers') }}">
                    <i class="menu-icon fa fa-bell-o fa-fw"></i>
                    <span class="menu-text"> اشعارات  مقدمى الخدمات</span>
                </a>
            </li>

        @endcan
        @can('show_users_notifications')
            <li class="{{ Request::is('mc33/notifications/list/users*') ? 'active' : '' }}">
                <a href="{{ route('admin.notifications','users') }}">
                    <i class="menu-icon fa fa-bell-o fa-fw"></i>
                    <span class="menu-text"> اشعارات  المستخدمين</span>
                </a>
            </li>

        @endcan

        @can('show_comments')
            <li class="{{ Request::is('mc33/comments') ? 'active' : '' }}">
                <a href="{{ route('admin.comments') }}">
                    <i class="menu-icon fa fa-comment-o fa-fw"></i>
                    <span class="menu-text">التعليقات  </span>
                </a>
            </li>
        @endcan

        @can('show_reports')
            <li class="{{ Request::is('mc33/reports') ? 'active' : '' }}">
                <a href="{{ route('admin.reports') }}">
                    <i class="menu-icon fa fa-file-o fa-fw"></i>
                    <span class="menu-text">   البلاغات    </span>
                </a>
            </li>
        @endcan


        @can('show_cancellation_reasons')
            <li class="{{ Request::is('mc33/cancelReasons*') ? 'active' : '' }}">
                <a href="{{ route('admin.reasons.index') }}">
                    <i class="menu-icon fa fa-inbox fa-fw"></i>
                    <span class="menu-text"> أسباب الرفض للطلبات  </span>
                </a>
            </li>
        @endcan

        @can('show_content')
            <li style="margin-bottom: 15px;" class="{{ Request::is('mc33/data/agreement*') ? 'active' : '' }}">
                <a href="{{ route('admin.data.agreement') }}">
                    <i class="menu-icon fa fa-file-text fa-fw"></i>
                    <span class="menu-text">  إتفاقية التسجيل وشروط الحجز وشروط مقدم  الخدمة </span>
                </a>
            </li>
        @endcan

        @can('show_balance')
            <li class="{{ Request::is('mc33/data/information*') ? 'active' : '' }}">
                <a href="{{ route('admin.data.information') }}">
                    <i class="menu-icon fa fa-key fa-fw"></i>
                    <span class="menu-text"> الارصدة </span>
                </a>
            </li>
        @endcan

        @can('show_settings')
            <li class="{{ Request::is('mc33/settings*') ? 'active' : '' }}">
                <a href="{{ route('admin.settings.index') }}">
                    <i class="menu-icon fa fa-file fa-fw"></i>
                    <span class="menu-text">  المحتوي </span>
                </a>
            </li>
        @endcan


        @can('share_application_setting')
        <li class="{{ Request::is('mc33/sharing*') ? 'active' : '' }}">
            <a href="{{ route('admin.sharing') }}">
                <i class="menu-icon fa fa-file fa-fw"></i>
                <span class="menu-text">  أعدادات مشاركه التطبيق </span>
            </a>
        </li>
        @endcan

        @can('show_development')
            <li class="{{ Request::is('mc33/development*') ? 'active' : '' }}">
                <a href="{{ route('admin.development.index') }}">
                    <i class="menu-icon fa fa-file fa-fw"></i>
                    <span class="menu-text">   عن  الشركة المطورة  </span>
                </a>
            </li>
        @endcan

        @can('show_bills')
            <li class="{{ Request::is('mc33/bills*') ? 'active' : '' }}">
                <a href="{{ route('admin.bills.index') }}">
                    <i class="menu-icon fa fa-file fa-fw"></i>
                    <span class="menu-text">   فواتير الحجوزات  </span>
                </a>
            </li>
        @endcan

        @can('show_subscriptions')
            <li class="{{ Request::is('mc33/subscriptions*') ? 'active' : '' }}">
                <a href="{{ route('admin.subscriptions.index') }}">
                    <i class="menu-icon fa fa-inbox fa-fw"></i>
                    <span class="menu-text">  القائمة البريدية  </span>
                </a>
            </li>
        @endcan

        @can('show_brands')
            <li class="{{ Request::is('mc33/brands*') ? 'active' : '' }}">
                <a href="{{ route('admin.brands') }}">
                    <i class="menu-icon fa fa-inbox fa-fw"></i>
                    <span class="menu-text">  الشعارات  </span>
                </a>
            </li>
        @endcan

        @can('random_drawing')
            <li class="{{ Request::is('mc33/lotteries/drawing*') ? 'active' : '' }}">
                <a href="{{ route('admin.lotteries.drawing') }}">
                    <i class="menu-icon fa fa-inbox fa-fw"></i>
                    <span class="menu-text">  السحب العشوائي  </span>
                </a>
            </li>
        @endcan

        @can('show_lotteries_users')
            <li class="{{ Request::is('mc33/lotteries/users*') ? 'active' : '' }}">
                <a href="{{ route('admin.lotteries.users') }}">
                    <i class="menu-icon fa fa-users fa-fw"></i>
                    <span class="menu-text"> الفائزين من السحب </span>
                </a>
            </li>
        @endcan
        <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
            <i id="sidebar-toggle-icon" class="ace-save-state ace-icon fa fa-angle-double-right"
               data-icon1="ace-icon fa fa-angle-double-right" data-icon2="ace-icon fa fa-angle-double-left"></i>
        </div>


    </ul>
</div>
