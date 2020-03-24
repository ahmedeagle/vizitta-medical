@if(Request::is('mc33/admins/add'))
@else

@endif

<tr>
    <td>شركات التأمين</td>
    <td>
        <input type="checkbox" name="show_insurance_company" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_insurance_company')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_insurance_company" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_insurance_company')) checked @endif @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_insurance_company" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','delete_insurance_company')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_insurance_company" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_insurance_company')) checked @endif @endif />
    </td>
</tr>

<tr>
    <td>مقدمي الخدمات</td>
    <td>
        <input type="checkbox" name="show_providers" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_providers')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_providers" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_providers')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_providers" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_providers')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_providers" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_providers')) checked @endif  @endif />
    </td>

</tr>

<tr>
    <td> الافرع</td>
    <td>
        <input type="checkbox" name="show_branches" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_branches')) checked @endif @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_branches" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_branches')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_branches" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_branches')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_branches" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_branches')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td>الأطباء</td>
    <td>
        <input type="checkbox" name="show_doctors" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_doctors')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_doctors" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_doctors')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_doctors" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','delete_doctors')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_doctors" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_doctors')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td>التخصصات</td>
    <td>
        <input type="checkbox" name="show_specialists" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_specialists')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_specialists" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_specialists')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_specialists" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_specialists')) checked @endif @endif />
    </td>
    <td>
        <input type="checkbox" name="add_specialists" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','add_specialists')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td>الالقاب</td>
    <td>
        <input type="checkbox" name="show_titles" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_titles')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_titles" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','edit_titles')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_titles" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_titles')) checked @endif   @endif />
    </td>
    <td>
        <input type="checkbox" name="add_titles" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_titles')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td>الحجوزات</td>
    <td>
        <input type="checkbox" name="show_reservations" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_reservations')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_reservations" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_reservations')) checked @endif   @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_reservations" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_reservations')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_reservations" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_reservations')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td>المستخدمين</td>
    <td>
        <input type="checkbox" name="show_users" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_users')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_users" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_users')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_users" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_users')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_users" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','add_users')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td>مستخدمي اللوحة</td>
    <td>
        <input type="checkbox" name="show_admins" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_admins')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_admins" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','edit_admins')) checked @endif @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_admins" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','delete_admins')) checked @endif @endif />
    </td>
    <td>
        <input type="checkbox" name="add_admins" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','add_admins')) checked @endif @endif />
    </td>
</tr>

<tr>
    <td>المدن</td>
    <td>
        <input type="checkbox" name="show_cities" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_cities')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_cities" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_cities')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_cities" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','delete_cities')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_cities" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_cities')) checked @endif   @endif />
    </td>
</tr>

<tr>
    <td> الأحياء</td>
    <td>
        <input type="checkbox" name="show_districts" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_districts')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_districts" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_districts')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_districts" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_districts')) checked @endif   @endif />
    </td>
    <td>
        <input type="checkbox" name="add_districts" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_districts')) checked @endif   @endif />
    </td>
</tr>

<tr>
    <td>الصفحات</td>
    <td>
        <input type="checkbox" name="show_pages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_pages')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_pages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_pages')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_pages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_pages')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_pages" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','add_pages')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td> الجنسيات</td>
    <td>
        <input type="checkbox" name="show_nationalities" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_nationalities')) checked @endif @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_nationalities" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_nationalities')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_nationalities" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_nationalities')) checked @endif   @endif />
    </td>
    <td>
        <input type="checkbox" name="add_nationalities" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_nationalities')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td> الكوبونات</td>
    <td>
        <input type="checkbox" name="show_coupons" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_coupons')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_coupons" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_coupons')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_coupons" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_coupons')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_coupons" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_coupons')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td> رسائل مقدمي الخدمات</td>
    <td>
        <input type="checkbox" name="show_provider_messages" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_provider_messages')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_provider_messages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_provider_messages')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_provider_messages" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','delete_provider_messages')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_provider_messages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_provider_messages')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td> رسائل المستخدمين</td>
    <td>
        <input type="checkbox" name="show_user_messages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_user_messages')) checked @endif   @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_user_messages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_user_messages')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_user_messages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_user_messages')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_user_messages" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','add_user_messages')) checked @endif  @endif />
    </td>
</tr>

<tr>
    <td> نص إتفاقية التسجيل وشروط الحجز</td>
    <td>
        <input type="checkbox" name="show_content" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','show_content')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="edit_content" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','edit_content')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="delete_content" value="1"
               @if(isset($permissions)) @if(@$permissions ->contains('name','delete_content')) checked @endif  @endif />
    </td>
    <td>
        <input type="checkbox" name="add_content" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','add_content')) checked @endif  @endif />
    </td>
</tr>


<tr>
    <td> أنواع مقدمي الخدمات</td>
    <td>
        <input type="checkbox" name="show_providers_types" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_providers_types')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> مستخدمي السحب العشوائي</td>
    <td>
        <input type="checkbox" name="show_lotteries_users" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_lotteries_users')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>

<tr>
    <td> السحب العشوائي</td>
    <td>
        <input type="checkbox" name="random_drawing" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','random_drawing')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> عرض عيادات السحب العشوائي</td>
    <td>
        <input type="checkbox" name="show_lotteries_branches" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_lotteries_branches')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> أنواع مقدمي الخدمات</td>
    <td>
        <input type="checkbox" name="show_providers_types" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_providers_types')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> أقسام الكوبونات</td>
    <td>
        <input type="checkbox" name="show_promoCategories" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_promoCategories')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> اشعارات مقدمي الخدمات</td>
    <td>
        <input type="checkbox" name="show_providers_notifications" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_providers_notifications')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> اشعارات المستخدمين</td>
    <td>
        <input type="checkbox" name="show_users_notifications" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_users_notifications')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td>التعليقات</td>
    <td>
        <input type="checkbox" name="show_comments" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_comments')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td>التقارير</td>
    <td>
        <input type="checkbox" name="show_reports" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_reports')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td>اسباب رفض الحجوزات</td>
    <td>
        <input type="checkbox" name="show_cancellation_reasons" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_cancellation_reasons')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> الارصده</td>
    <td>
        <input type="checkbox" name="show_balance" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_balance')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> الاعدادات</td>
    <td>
        <input type="checkbox" name="show_settings" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_settings')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> الشركة المطورة</td>
    <td>
        <input type="checkbox" name="show_development" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_development')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> الفواتير</td>
    <td>
        <input type="checkbox" name="show_bills" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_bills')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> الاشتراكات</td>
    <td>
        <input type="checkbox" name="show_subscriptions" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_subscriptions')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> الشعارات</td>
    <td>
        <input type="checkbox" name="show_brands" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','show_brands')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>

<tr>
    <td> التنبيهات</td>
    <td>
        <input type="checkbox" name="notifications" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','notifications')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>


<tr>
    <td> أعدادات مشاركة التطبيق</td>
    <td>
        <input type="checkbox" name="share_application_setting" value="1"
               @if(isset($permissions))  @if(@$permissions ->contains('name','share_application_setting')) checked @endif  @endif />
    </td>

    <td>
    </td>

    <td>
    </td>
    <td>
    </td>
</tr>





