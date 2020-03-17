@extends('emails.layout')

@section('mail_content')
    <li>{{ $provider_name }},</li>
    <li>{{ trans('messages.You have a new reservation') }}</li>
    <li><b>{{ trans('messages.By day') }}: </b>{{ $day_name }}</li>
    <li><b>{{ trans('messages.By date') }}: </b>{{ $day_date }}</li>
    <li><b>{{ trans('messages.Clock') }}: </b>{{ $from_time }} {{ trans('messages.to') }} {{ $to_time }}</li>
    <br>
    <li>{{ trans('messages.You can login to your account to manage the reservation') }}</li>
@stop