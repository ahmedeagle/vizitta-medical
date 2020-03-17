<?php  $count = 0;  ?>
<small class="text-danger">{{ $errors->has('working_days') ? $errors->first('working_days') : '' }}</small>
@if(Session::has('working_day'))
    <small class="text-danger"> {{Session::get('working_day')}}</small>

@endif
<p style="color: red">  ادخل التوقيت من والي وثم اضغط علي علامه اضافه لاضافه اليوم </p>

@if(!empty($days) &&  count($days) > 0)
    @foreach($days as $index => $day)
        <tr class="timerow order{{$index.$count}}">
                <th style="border-top: 0px"
                    scope="row">{{ Form::checkbox('day',$index, null,['style' =>'display:none' ,'class' => 'availableDay day'.$index.$count])}}</th>
                <td style="border-top: 0px">{{$day}}</td>
                <td style="border-top: 0px"> {{ Form::time('from','', ['class' => 'form-control from' .  $index.$count ]) }}</td>

                <td style="border-top: 0px">{{ Form::time('to','', [ 'class' => 'form-control to' . $index.$count ]) }}</td>
                <td style="border-top: 0px" class="add_minus{{$index.$count}}"><i data_counter="{{$count}}"
                                                                                  data_day_en={{$index}} data_day_ar={{$day}}  class="fa fa-plus fa-2x addShiftTime"></i></td>
        </tr>
        <?php  $count++;  ?>
    @endforeach
@endif

