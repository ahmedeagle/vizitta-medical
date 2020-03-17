@section('main')
    <?php  $counter++;  ?>
    <tr class="timerow order{{$day_en.$counter}}">
        <th style="border-top: 0px" scope="row">{{ Form::checkbox('day',$day_en, true,['style' =>'display:none' ,'class' => 'availableDay day'.$day_en.$counter])}}</th>
        <td style="border-top: 0px">{{$day_ar}}</td>
        <td style="border-top: 0px"> {{ Form::time('from','', [ 'class' => 'form-control  from'.$day_en.$counter]) }}</td>

        <td style="border-top: 0px">{{ Form::time('to','', ['class' => 'form-control  to'.$day_en.$counter]) }}</td>
        <td style="border-top: 0px"  class="add_minus{{$day_en.$counter}}"><i   data_counter="{{$counter}}" data_day_en={{$day_en}} data_day_ar={{$day_ar}}  class="fa fa-plus fa-2x addShiftTime"> </i> </td>
    </tr>
    @stop


