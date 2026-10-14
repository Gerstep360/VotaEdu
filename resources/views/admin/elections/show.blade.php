@extends('layouts.app')
@section('title', $election->title)
@section('content')
@include('partials.election-detail')
@endsection
