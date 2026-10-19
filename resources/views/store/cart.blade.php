@extends('layouts.store')
@section('title', 'Your cart')
@section('content')
<div class="container shopping-page" data-cart-fallback>
    <h1>Shopping cart</h1>
    <div data-cart-page-content>@include('store.partials.cart-content')</div>
</div>
@endsection
