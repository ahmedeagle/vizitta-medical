@extends('emails.layout')

@section('mail_content')
    <li> هنالك رد جديد من ({{$user_name}})</li>
    <li>لقراءة الرسالة والرد عليها يرجى زيارة لوحة التحكم</li>
@stop