@extends('layouts.admin')
@section('title', 'Analytics')
@section('content')
<div class="analytics-page">
    <x-admin-heading title="Analytics" description="Sales, demand, and inventory in one place.">
        <a class="text-link" href="{{ route('admin.analytics.export', request()->only('period', 'from', 'to')) }}">Export summary CSV <x-icon name="download" /></a>
    </x-admin-heading>
    <form method="get" action="{{ route('admin.analytics') }}" class="analytics-filters" data-analytics-form>
        <label class="field">Period<select name="period">
            @foreach(['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 12 months', 'all' => 'All time', 'custom' => 'Custom dates'] as $value => $label)
                <option value="{{ $value }}" @selected($period === (string) $value)>{{ $label }}</option>
            @endforeach
        </select></label>
        <label class="field">From<input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
        <label class="field">To<input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
        <button class="button button-dark" type="submit">Apply dates</button>
        <button class="text-link" type="button" data-analytics-refresh><x-icon name="refresh" />Refresh</button>
    </form>
    <p class="analytics-update"><span>Orders: {{ $from->format('j M Y') }} – {{ $to->format('j M Y') }}</span><span data-analytics-updated>Updated {{ now()->format('H:i:s') }} · refreshes every 30 seconds</span></p>
    <div data-analytics-report data-url="{{ request()->fullUrl() }}">@include('admin.analytics.report')</div>
</div>
@endsection
