@extends('emails.layout')

@section('mail_content')
    <li>لقد تم رفض حجزك رقم ({{ $reservation_no }}) لسبب ({{ $reason }})</li>
@stop