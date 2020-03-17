<?php  $count=0;  ?>
<small class="text-danger">{{ $errors->has('working_days') ? $errors->first('working_days') : '' }}</small>
@if(Session::has('working_day'))
    <small class="text-danger"> {{Session::get('working_day')}}</small>

@endif


@if(isset($times) && $times -> count() > 0)
    @foreach($times as $index => $time)
        <tr class="timerow order{{$time -> day_code.$count}}">
            <th style="border-top: 0px"
                scope="row">{{ Form::checkbox('day',$time -> day_code, null,['style' =>'display:none' ,'class' => 'availableDay day'.$time -> day_code.$count])}}</th>
            <td style="border-top: 0px">{{$time['day_name']}}</td>
            <td style="border-top: 0px"> {{ Form::time('from',$time['from_time'], ['disabled' => 'true','class' => 'form-control from' .  $time -> day_code.$count ]) }}</td>
            <td style="border-top: 0px">{{ Form::time('to',$time['to_time'], [ 'disabled' => 'true','class' => 'form-control to' . $time -> day_code.$count ]) }}</td>
            <td style="border-top: 0px"><i  data_from="{{$time['from_time']}}" data_to="{{$time['to_time']}}"  data_day_en="{{$time -> day_code}}"  data_day_ar="{{$time['day_name']}}"   data_id="{{$time['id']}}" class="fa fa-close fa-2x removeEditTime" ></i></td>
        </tr>
        <?php  $count++;  ?>
    @endforeach
@endif

<tr>
    <th scope="row"></th>
    <td> </td>
    <td><p style="text-align: center;font-size:20px;color: red;">اضافة مواعيد جديد </p></td>

    <td></td>
    <td style="border-top: 0px"></td>
</tr>
<p style="color: red">  ادخل التوقيت من والي وثم اضغط علي علامه اضافه لاضافه اليوم </p>

@if(!empty($days) &&  count($days) > 0)
    @foreach($days as $index => $day)
        <tr class="timerow order{{$index.$count}}">
            <th style="border-top: 0px"
                scope="row">{{ Form::checkbox('day',$index, null,['style' =>'display:none' ,'class' => 'availableDay day'.$index.$count])}}</th>
            <td style="border-top: 0px">{{$day}}</td>
            <td style="border-top: 0px"> {{ Form::time('from','', ['class' => 'form-control from' .  $index.$count ]) }}</td>

            <td style="border-top: 0px">{{ Form::time('to','', [ 'class' => 'form-control to' . $index.$count ]) }}</td>
            <td style="border-top: 0px" class="add_minus{{$index.$count}}">
                <i data_counter="{{$count}}"
                                                                              data_day_en={{$index}} data_day_ar={{$day}}  class="fa fa-plus fa-2x addShiftTime"></i></td>
        </tr>
        <?php  $count++;  ?>
    @endforeach
@endif

