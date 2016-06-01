@extends('front._master')

@section('css')

@endsection

@section('js')

@endsection

@section('js-init')

@endsection

@section('content')
    <h1>{{ $object->title }}</h1>
    <h1>Fashion product category template</h1>
    @foreach($relatedProducts as $key => $row)
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><a href="{{ _getProductLink($row, $currentLanguageCode) }}" title="{{ $row->title or '' }}">{{ $row->title or '' }}</a></h3>
            </div>
            <div class="panel-body">
                <div class="text-justify">{!! $row->content or '' !!}</div>
            </div>
        </div>
    @endforeach
    @include('front._modules._pagination', ['paginator' => $relatedProducts])
@endsection