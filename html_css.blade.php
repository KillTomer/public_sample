@extends('layouts.app')

@section('title', '12 star signs in astrology')
@section('description', 'There are 12 astrological signs. Visit personality section that spotlights relationships with
    friends, family, and loved ones. Complete information about astrology zodiac signs Aries, Taurus, Gemini, Cancer, Leo,
    Virgo, Libra, Scorpio, Sagittarius, Capricorn, Aquarius and Pisces, dates and meanings.')
    @section('json_ld'){!! $json_ld !!}@endsection

@section('content')
    <div class="mx-auto max-w-text">
        <h2 class="h1">Star signs of the zodiac in astrology</h2>

        <section class="rounded-md px-2 pb-2 sm:px-4 bg-gradient-to-r from-green-400 to-blue-500 text-white">
            <h3 class="pt-4 pb-1 h2 text-shadow">Zodiac signs personality</h3>
            <p class="pb-4 text-sm sm:text-base text-shadow-2">Visit personality section that spotlights relationships with friends, family, and loved ones. Complete information about astrology zodiac signs</p>
            <div class="rounded bg-gray-50 bg-opacity-30 p-2 pb-0 sm:-mx-2">
                @include('body.zodiac_list')
            </div>
        </section>

        @include('body.spotify_link')

        @include('body.cusps_nav')

        @foreach ($zodiacs as $key => $value)
            <article class="mb-10">
                <h3 class="font-bold text-2xl text-purple-600">Star sign {{ ucfirst($key) }}</h3>
                <p class="font-bold text-base">{{ ucfirst($key) }} dates: ({{ $value['date'] }})</p>
                <p class="mt-4">{!! $value['brief'] !!}</p>
                <p class="mt-3">Click to see
                    <a class="underline  font-bold" href="{{ route('personality', ['zodiac' => $key]) }}">full
                        {{ ucFirst($key) }} personality</a>
                </p>
            </article>
            @if ($loop->iteration == 1)
                {{-- ADS --}}
                <div class="mt-2 -mb-3">@include('ads.in_article')</div>
            @endif
        @endforeach
    </div>
@endsection
