@extends('layouts.store')
@section('title', 'Checkout')
@section('hide-footer', true)
@section('content')
<div class="container shopping-page" data-cart-fallback data-start-phase="checkout">
    <h1>Checkout</h1>
    <div data-cart-page-content>@include('store.partials.cart-content')</div>
</div>
@endsection
