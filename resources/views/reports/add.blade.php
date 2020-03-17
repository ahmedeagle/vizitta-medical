@extends('layouts.master')

@section('title', 'إضافة اشعار جديد')

@section('styles')
    {!! Html::style('css/form.css') !!}
    <style>
        .fade {
            opacity: 0;
            -webkit-transition: opacity .15s linear;
            -o-transition: opacity .15s linear;
            transition: opacity .15s linear;
        }

        .custom-control {
            position: relative;
            display: block;
            min-height: 1.5rem;
            padding-left: 1.5rem;
        }

        .custom-control-label {
            position: relative;
            margin-top: 15px;
            vertical-align: top;
        }
    </style>
@stop

@section('content')
@section('breadcrumbs')
    @if($type == 'providers')
        {!! Breadcrumbs::render('add.notifications.providers') !!}
    @else
        {!! Breadcrumbs::render('add.notifications.users') !!}
    @endif

@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-magic"></i> إضافة اشعار </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::open(['route' => ['admin.notifications.post'], 'class' => 'form']) }}

        <div class="form-group has-float-label col-sm-12">
            {{ Form::text('title', old('title'), ['placeholder' => 'عنوان الاشعار',  'class' => 'form-control ' . ($errors->has('title') ? 'redborder' : '') ]) }}
            <label for="name_ar">عنوان الاشعار <span class="astric">*</span></label>
            <small class="text-danger">{{ $errors->has('title') ? $errors->first('title') : '' }}</small>
        </div>

        <div class="form-group has-float-label col-sm-12">
            {{ Form::text('content', old('content'), ['placeholder' => 'محتوي الاشعار  ',  'class' => 'form-control ' . ($errors->has('content') ? 'redborder' : '') ]) }}
            <label for="name_en"> محتوي الاشعار <span class="astric">*</span></label>
            <small class="text-danger">{{ $errors->has('content') ? $errors->first('content') : '' }}</small>
        </div>
        <input type="hidden" name="type" value="{{$type}}">
        <div class="form-group has-float-label col-sm-12">
            <select id="select-notify-type" name="notify-type" class="form-control">
                <option value="0">برجاء اختيار نوع الارسال</option>
                <option value="1">ارسال الى الكل</option>
                <option value="2"> تحديد الارسال</option>
            </select>
            <label for="district_id"> نوع الارسال </label>
            <small class="text-danger">{{ $errors->has('notify-type') ? $errors->first('notify-type') : '' }}</small>
        </div>

        <small class="text-danger">{{ $errors->has('receivers') ? $errors->first('receivers') : '' }}</small>
        <div id="choose-users-container" class="form-group row receivers" style="display: none;">
            <label class="col-sm-2 col-form-label">مستقبلى الاشعار</label>
            <div class="col-sm-10">
                <button type="button" data-toggle="modal" data-target="#selectActors" class="btn btn-default"><i
                        class="fa fa-plus-circle"></i> اختيار من القائمة
                </button>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade in" id="selectActors" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">اختيار مستقبلى الاشعار</h4>
                    </div>
                    <div class="modal-body">
                        <input type="text" class="form-control"  id="myInput" onkeyup="seachRes()"  placeholder="بحث"/>
                        <div id="notification-container" style="overflow-y: auto;max-height: 371px;"
                             class="user-box assign-user taskboard-right-users">
                            @if(isset($receivers) && !empty($receivers) && count($receivers) > 0)
                                @foreach($receivers as $receiver)
                                    <div class="form-group custom-checkbox divsearch">
                                        <input type="checkbox" class="custom-control-input form-group" value="{{$receiver -> id}}"
                                               name="ids[]">
                                        <label class="custom-control-label form-group names"
                                               for="defaultUnchecked"> {{$receiver -> name}}</label>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button  type="button" style="margin-left: 10px;" class="add-btn-list btn btn-success dismise">اضافة الى القائمة
                        </button>

                    </div>
                </div>
            </div>
        </div>


        <div class="form-group col-sm-12 submit">
            {{ Form::submit('حفظ', ['class' => 'btn btn-sm' ]) }}
        </div>
        {{ Form::close() }}
    </div>
</div>

@stop


@section('scripts')
    <script>

        function seachRes() {
            var input, filter, ul, li, a, i, txtValue;
            input = document.getElementById("myInput");
            filter = input.value.toUpperCase();
            ul = document.getElementById("notification-container");
            li = ul.getElementsByTagName("div");
             for (i = 0; i < li.length; i++) {
                 a = li[i].getElementsByTagName("label")[0];
                txtValue = a.textContent || a.innerText;
                 if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    li[i].style.display = "";
                } else {
                    li[i].style.display = "none";
                }
            }
        }


        $(document).on('click','.dismise',function(){
            $('#selectActors').modal('toggle');
        });
        $(document).on('change', '#select-notify-type', function () {
            val = $(this).val();
            alert
            if (val == 2) {
                $('.receivers').show();
            } else {
                $('.receivers').hide();
            }
        });
        /*  $(document).on('click','.receivers',function(e){
              e.preventDefault();
          });*/
    </script>
@stop
