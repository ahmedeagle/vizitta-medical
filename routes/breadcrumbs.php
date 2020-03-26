<?php
// Dashboard
Breadcrumbs::for('dashboard', function ($trail) {
    $trail->push('الرئيسية', route('home'));
});

/******************************************************************************/

// Home > Insurance Company
Breadcrumbs::for('insurance.company', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('شركات التأمين', route('admin.insurance.company'));
});
// Home > Insurance Company > Add
Breadcrumbs::for('add.company.insurance', function ($trail) {
    $trail->parent('insurance.company');
    $trail->push('إضافة شركة تأمين');
});
// Home > Insurance Company > Edit
Breadcrumbs::for('edit.company.insurance', function ($trail) {
    $trail->parent('insurance.company');
    $trail->push('تعديل شركة تأمين');
});

/******************************************************************************/

// Home > Specification
Breadcrumbs::for('specification', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('تخصصات الاطباء', route('admin.specification'));
});
// Home > ٍ Specification > Add
Breadcrumbs::for('add.specification', function ($trail) {
    $trail->parent('specification');
    $trail->push('إضافة تخصص');
});
// Home > Specification > Edit
Breadcrumbs::for('edit.specification', function ($trail) {
    $trail->parent('specification');
    $trail->push('تعديل تخصص');
});

/******************************************************************************/


// Home > promoCategories
Breadcrumbs::for('promoCategories', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('أقسام الكوبونات ', route('admin.promoCategories'));
});
// Home > ٍ promoCategories > Add
Breadcrumbs::for('add.promoCategories', function ($trail) {
    $trail->parent('promoCategories');
    $trail->push('إضافة  قسم جديد ');
});
// Home > promoCategories > Edit
Breadcrumbs::for('edit.promoCategories', function ($trail) {
    $trail->parent('promoCategories');
    $trail->push('تعديل  قسم ');
});
//reorder

Breadcrumbs::for('reorder', function ($trail) {
    $trail->parent('promoCategories');
    $trail->push('ترتيب اقسام الكوبونات ', route('admin.promoCategories'));
});


/******************************************************************************/

// Home > types
Breadcrumbs::for('types', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('   انواع مقدمي الحدمات ', route('admin.types'));
});
// Home > ٍ Specification > Add
Breadcrumbs::for('add.types', function ($trail) {
    $trail->parent('types');
    $trail->push('إضافة  نوع مفدم خدمة ');
});


// Home > Specification > Edit
Breadcrumbs::for('edit.types', function ($trail) {
    $trail->parent('types');
    $trail->push('تعديل  نوغ مفدم الخدمة ');
});

/******************************************************************************/

// Home > City
Breadcrumbs::for('city', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('المدن', route('admin.city'));
});
// Home > ٍ City > Add
Breadcrumbs::for('add.city', function ($trail) {
    $trail->parent('city');
    $trail->push('إضافة مدينة');
});
// Home > City > Edit
Breadcrumbs::for('edit.city', function ($trail) {
    $trail->parent('city');
    $trail->push('تعديل مدينة');
});

/******************************************************************************/

// Home > District
Breadcrumbs::for('district', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('الأحياء', route('admin.district'));
});
// Home > ٍ District > Add
Breadcrumbs::for('add.district', function ($trail) {
    $trail->parent('district');
    $trail->push('إضافة حى');
});
// Home > District > Edit
Breadcrumbs::for('edit.district', function ($trail) {
    $trail->parent('district');
    $trail->push('تعديل حى');
});

/******************************************************************************/

// Home > Nickname
Breadcrumbs::for('nickname', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('ألقاب  الاطباء ', route('admin.nickname'));
});
// Home > Nickname > Add
Breadcrumbs::for('add.nickname', function ($trail) {
    $trail->parent('nickname');
    $trail->push('إضافة لقب');
});
// Home > Nickname > Edit
Breadcrumbs::for('edit.nickname', function ($trail) {
    $trail->parent('nickname');
    $trail->push('تعديل لقب');
});

/******************************************************************************/

// Home > Nationality
Breadcrumbs::for('nationality', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('الجنسيات', route('admin.nationality'));
});
// Home > Nationality > Add
Breadcrumbs::for('add.nationality', function ($trail) {
    $trail->parent('nationality');
    $trail->push('إضافة جنسية');
});
// Home > Nationality > Edit
Breadcrumbs::for('edit.nationality', function ($trail) {
    $trail->parent('nationality');
    $trail->push('تعديل جنسية');
});

/******************************************************************************/

// Home > Promo Codes
Breadcrumbs::for('promoCode', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('الرموز الترويجية', route('admin.promoCode'));
});

// Home > Promo Codes > branches
Breadcrumbs::for('promoCode-branches', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('افرع الكوبون ', route('admin.promoCode.branches', ['id', '*']));
});


// Home > Promo Codes >  doctor
Breadcrumbs::for('promoCode-doctors', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('افرع الكوبون ', route('admin.promoCode.doctors', ['id', '*']));
});

// Home > Promo Codes > Add
Breadcrumbs::for('add.promoCode', function ($trail) {
    $trail->parent('promoCode');
    $trail->push('إضافة رمز');
});

Breadcrumbs::for('add.filter', function ($trail) {
    $trail->parent('promoCode');
    $trail->push('إضافة  فلتر');
});


Breadcrumbs::for('mostreserved', function ($trail) {
    $trail->parent('promoCode');
    $trail->push('أكثر العروض حجزا');
});

Breadcrumbs::for('edit.filter', function ($trail) {
    $trail->parent('promoCode');
    $trail->push('تعديل فلتر');
});

// Home > Promo Codes > Edit
Breadcrumbs::for('edit.promoCode', function ($trail) {
    $trail->parent('promoCode');
    $trail->push('تعديل رمز');
});
// Home > Promo Codes > View
Breadcrumbs::for('view.promoCode', function ($trail) {
    $trail->parent('promoCode');
    $trail->push('تفاصيل رمز الخصم');
});

/******************************************************************************/

// Home > Provider
Breadcrumbs::for('provider', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('مقدمى الخدمة', route('admin.provider'));
});
// Home > Provider > Edit
Breadcrumbs::for('edit.provider', function ($trail) {
    $trail->parent('provider');
    $trail->push('تعديل مقدم الخدمة');
});

// Home > Provider > Create
Breadcrumbs::for('add.provider', function ($trail) {
    $trail->parent('provider');
    $trail->push(' اضافة مقدم الخدمة');
});


// Home > Provider > View
Breadcrumbs::for('view.provider', function ($trail) {
    $trail->parent('provider');
    $trail->push('تفاصيل مقدم الخدمة');
});


Breadcrumbs::for('providers.reservation', function ($trail) {
    $trail->parent('provider');
    $trail->push('الحجوزات ');
});

/******************************************************************************/

// Home > Admin > View Agreement
Breadcrumbs::for('agreement', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('نص الإتفاقية', route('admin.data.agreement'));
});
// Home > Admin > Edit Agreement
Breadcrumbs::for('edit.agreement', function ($trail) {
    $trail->parent('agreement');
    $trail->push('تعديل نص الإتفاقية');
});

/******************************************************************************/

// Home > Custom Page
Breadcrumbs::for('customPage', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('الصفحات الفرعية', route('admin.customPage'));
});
// Home > Custom Page > Add
Breadcrumbs::for('add.customPage', function ($trail) {
    $trail->parent('customPage');
    $trail->push('إضافة صفحة فرعية');
});
// Home > Custom Page > Edit
Breadcrumbs::for('edit.customPage', function ($trail) {
    $trail->parent('customPage');
    $trail->push('تعديل صفحة فرعية');
});
// Home > Custom Page > View
Breadcrumbs::for('view.customPage', function ($trail) {
    $trail->parent('customPage');
    $trail->push('تفاصيل صفحة فرعية');
});

/******************************************************************************/

// Home > Admin > View Information
Breadcrumbs::for('information', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('بيانات التطبيق', route('admin.data.information'));
});
// Home > Admin > Edit Information
Breadcrumbs::for('edit.information', function ($trail) {
    $trail->parent('information');
    $trail->push('تعديل بيانات التطبيق');
});

// Home > Admin > Edit Information > branched balance
Breadcrumbs::for('branches.balance', function ($trail) {
    $trail->parent('edit.information');
    $trail->push(' عرض رصيد الافرع ');
});

/******************************************************************************/

// Home > Admin > Edit Provider Balance
Breadcrumbs::for('edit.provider.balance', function ($trail) {
    $trail->parent('information');
    $trail->push('تعديل رصيد فرع  ');
});

/******************************************************************************/

// Home > Doctor
Breadcrumbs::for('doctor', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('الاطباء ', route('admin.doctor'));
});
// Home > Doctor > Edit
Breadcrumbs::for('edit.doctor', function ($trail) {
    $trail->parent('doctor');
    $trail->push('تعديل  الطبيب ');
});
// Home > Doctor > View
Breadcrumbs::for('view.doctor', function ($trail) {
    $trail->parent('doctor');
    $trail->push('تفاصيل  الطبيب ');
});

/******************************************************************************/

// Home > Branch
Breadcrumbs::for('branch', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('الفروع', route('admin.branch'));
});

// Home > Branch > Edit
Breadcrumbs::for('add.branch', function ($trail) {
    $trail->parent('branch');
    $trail->push(' أضافة  فرع مقدم الخدمة');
});


// Home > Branch > Edit
Breadcrumbs::for('edit.branch', function ($trail) {
    $trail->parent('branch');
    $trail->push('تعديل فرع مقدم الخدمة');
});
// Home > Branch > View
Breadcrumbs::for('view.branch', function ($trail) {
    $trail->parent('branch');
    $trail->push('تفاصيل فرع مقدم الخدمة');
});

/******************************************************************************/

// Home > Reservation
Breadcrumbs::for('reservation', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('الحجوزات', route('admin.reservation'));
});
// Home > Reservation > Edit
Breadcrumbs::for('edit.reservation', function ($trail) {
    $trail->parent('reservation');
    $trail->push('تعديل حجز');
});
// Home > Reservation > View
Breadcrumbs::for('view.reservation', function ($trail) {
    $trail->parent('reservation');
    $trail->push('تفاصيل حجز');
});

/******************************************************************************/

// Home > User Message
Breadcrumbs::for('user.message', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('رسائل المستخدمين', route('admin.user.message'));
});
// Home > User Message > View
Breadcrumbs::for('view.user.message', function ($trail) {
    $trail->parent('user.message');
    $trail->push('تفاصيل الرسالة');
});


// Home > User  notifications
Breadcrumbs::for('user.notifications', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('اشعارات المستخدمين ', route('admin.notifications', 'users'));
});


Breadcrumbs::for('add.notifications.users', function ($trail) {
    $trail->parent('dashboard');
    $trail->push(' اضافة اسعار جديد', route('admin.notifications', 'users'));
});

Breadcrumbs::for('add.notifications.providers', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('اضافة اشعار جديد ', route('admin.notifications', 'providers'));
});


// Home > provider notification
Breadcrumbs::for('provider.notifications', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('اشعارات  مقدمي الخدمات ', route('admin.notifications', 'providers'));
});


/******************************************************************************/

// Home > Provider Message
Breadcrumbs::for('provider.message', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('رسائل مقدمى الخدمة', route('admin.provider.message'));
});
// Home > Provider Message > View
Breadcrumbs::for('view.provider.message', function ($trail) {
    $trail->parent('provider.message');
    $trail->push('تفاصيل الرسالة');
});

/******************************************************************************/

// Home > User
Breadcrumbs::for('user', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('المستخدمين', route('admin.user'));
});
// Home > User > View
Breadcrumbs::for('view.user', function ($trail) {
    $trail->parent('user');
    $trail->push('تفاصيل المستخدم');
});

/******************************************************************************/
// Home > admins
Breadcrumbs::for('admins', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('مستخدمي لوحة التحكم ', route('admin.admins'));
});
// Home > admin > View
Breadcrumbs::for('view.admins', function ($trail) {
    $trail->parent('admins');
    $trail->push('تفاصيل المستخدم');
});


// Home > admin > Edit
Breadcrumbs::for('edit.admins', function ($trail) {
    $trail->parent('admins');
    $trail->push('تعديل مستخدم');
});


// Home > admin > add
Breadcrumbs::for('add.admins', function ($trail) {
    $trail->parent('admins');
    $trail->push(' اضافة مستخدم');
});


Breadcrumbs::for('comments', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('  التعليقات  ');
});

Breadcrumbs::for('reports', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('   البلاغات   ');
});

Breadcrumbs::for('general', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('    عام    ');
});

Breadcrumbs::for('subscribtions', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('    القائمة البريدية     ');
});

Breadcrumbs::for('reasons', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('  اسباب الرفض  ');
});

Breadcrumbs::for('add.reasons', function ($trail) {
    $trail->parent('reasons');
    $trail->push('  اضافة  ');
});

Breadcrumbs::for('edit.reasons', function ($trail) {
    $trail->parent('reasons');
    $trail->push('  تعديل  ');
});

Breadcrumbs::for('bills', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('  فواتير الحجوزات   ');
});

Breadcrumbs::for('bills.view', function ($trail) {
    $trail->parent('bills');
    $trail->push(' التفاصيل   ');
});


// Home > brands
Breadcrumbs::for('brands', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('الشعارات', route('admin.brands'));
});
// Home > brands > Add
Breadcrumbs::for('add.brands', function ($trail) {
    $trail->parent('brands');
    $trail->push('إضافة شعار');
});

/******************************************************************************/

// Home > lotteries
Breadcrumbs::for('lotteries', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('عيادات السحب العشوائي ');
});

// Home > lotteries
Breadcrumbs::for('drawing', function ($trail) {
    $trail->parent('dashboard');
    $trail->push(' السحب والهدايا ');
});


// Home > District
Breadcrumbs::for('sharing', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('أعدادت مشاركه التطبيق ', route('admin.sharing'));
});

#####################################################################

// Home > offerCategories
Breadcrumbs::for('offerCategories', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('أقسام العروض ', route('admin.offerCategories'));
});
// Home > ٍ offerCategories > Add
Breadcrumbs::for('add.offerCategories', function ($trail) {
    $trail->parent('offerCategories');
    $trail->push('إضافة  قسم جديد ');
});
// Home > offerCategories > Edit
Breadcrumbs::for('edit.offerCategories', function ($trail) {
    $trail->parent('offerCategories');
    $trail->push('تعديل  قسم ');
});
//reorder

Breadcrumbs::for('reorderCategories', function ($trail) {
    $trail->parent('offerCategories');
    $trail->push('ترتيب اقسام العروض ', route('admin.offerCategories'));
});

//////////////////////////////////////////////////////////////////////////////////////////////

// Home > Offers
Breadcrumbs::for('offers', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('العروض', route('admin.offers'));
});

// Home > Promo Codes > branches
Breadcrumbs::for('offers-branches', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('افرع العرض ', route('admin.offers.branches', ['id', '*']));
});

// Home > Promo Codes > Add
Breadcrumbs::for('add.offers', function ($trail) {
    $trail->parent('offers');
    $trail->push('إضافة عرض');
});

// Home > Promo Codes > Add
Breadcrumbs::for('notifications.center', function ($trail) {
    $trail->parent('dashboard');
    $trail->push('التنبيهات');
});

/*Breadcrumbs::for('add.filter', function ($trail) {
    $trail->parent('offers');
    $trail->push('إضافة  فلتر');
});


Breadcrumbs::for('mostreserved', function ($trail) {
    $trail->parent('offers');
    $trail->push('أكثر العروض حجزا');
});

Breadcrumbs::for('edit.filter', function ($trail) {
    $trail->parent('offers');
    $trail->push('تعديل فلتر');
});*/

// Home > Promo Codes > Edit
Breadcrumbs::for('edit.offers', function ($trail) {
    $trail->parent('offers');
    $trail->push('تعديل عرض');
});
// Home > Promo Codes > View
Breadcrumbs::for('view.offers', function ($trail) {
    $trail->parent('offers');
    $trail->push('تفاصيل العرض');
});

#####################################################################


Breadcrumbs::for('banners', function ($trail) {
    $trail->parent('offers');
    $trail->push('بنرات العروض');
});
// Home > Promo Codes > View
Breadcrumbs::for('banners.create', function ($trail) {
    $trail->parent('banners');
    $trail->push(' أضافه بانر عرض');
});
