@extends('layouts.master')

@section('title', 'عرض تفاصيل الفاتوره   ')

@section('styles')
    <style>
        .profile-picture {
            border: 0 !important;
            box-shadow: none !important;
        }

        .icon {
            margin-left: 5px;
        }

        .disabled a {
            background-color: #b1b1b1 !important;
        }

        .widget-box {
            min-height: 100px !important;
        }
    </style>
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('bills.view') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-image"></i> الحجز رقم {{ $billData ->reservation_no }}</h1>
        </div>
    </div>

    <div class="col-md-12">
        <div class="profile-user-info profile-user-info-striped">
            <div class="profile-info-row">
                <div class="profile-info-name">رقم الحجز</div>
                <div class="profile-info-value">
                    <span class="editable">{{  $billData ->reservation_no  }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name">اسم مقدم الخدمة</div>
                <div class="profile-info-value">
                    <span class="editable">{{ $billData-> reservation['mainprovider']  }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> اسم الفرع</div>
                <div class="profile-info-value">
                    <span class="editable">{{$billData-> reservation['branch_name'] }}</span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> سعر الكشف</div>
                <div class="profile-info-value">
                    <span class="editable">
                       {{ $billData-> reservation['price'] }}
                    </span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name"> اجمالي الفاتوره المدخله من التاجر</div>
                <div class="profile-info-value">
                    <span class="editable">
                       {{  $billData-> reservation['bill_total']  }}
                    </span>
                </div>
            </div>


            <div class="profile-info-row">
                <div class="profile-info-name"> تقييم الطبيب</div>
                <div class="profile-info-value">
                    <span class="editable">
                              {{  $billData-> reservation['doctor_rate']  }}
                    </span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> تقييم التاجر</div>
                <div class="profile-info-value">
                    <span class="editable">  {{  $billData-> reservation['provider_rate']  }} </span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> صوره الفاتوره</div>
                <div class="profile-info-value">
                    <span class="editable"> <img style="max-height: 200px;" data-preview-image="{{ $billData->  photo}}"  src="{{  $billData->  photo  }}">  </span>
                </div>
            </div>


        </div>
        <div class="space-12"></div>

    </div>
</div>
@stop

@section('scripts')
    {!! Html::script('js/preview-image.js') !!}

    <script>
        $.previewImage({
            'xOffset': 10,  // x-offset from cursor
            'yOffset': 10,  // y-offset from cursor
            'fadeIn': 1000, // delay in ms. to display the preview
            'css': {        // the following css will be used when rendering the preview image.
                'padding': '20px',
                'border': '5px solid black'
            }
        });
    </script>
@stop
