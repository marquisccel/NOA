@php
    $ayam = fn (string $f) => asset('images/ayam/'.$f).'?v='.@filemtime(public_path('images/ayam/'.$f));
@endphp
{{-- Maskot ayam: badan utuh (ekor dan jengger menyatu, piksel asli), dan mata asli yang bergerak di dalam wajah. Matanya mengikuti kursor (ayam2d.js). --}}
<div data-ayam {{ $attributes->merge(['class' => 'absolute inset-x-0']) }} style="opacity:0;transition:opacity .8s">
    <button type="button" data-colek aria-label="Colek ayam" class="ayam-tombol">
        <span class="ayam-lompat" data-lompat>
            <span class="ayam-bayang"></span>
            <span class="ayam-adegan" data-adegan2>
                <span class="ayam-napas">
                    <img class="ayam-lapis" src="{{ $ayam('badan.webp') }}" alt="" draggable="false">
                    <span class="ayam-mata" data-kepala style="-webkit-mask-image:url('{{ $ayam('kepala.webp') }}');mask-image:url('{{ $ayam('kepala.webp') }}')">
                        <span class="ayam-oko" style="left:63.25%;top:19.37%;width:10.13%;height:7.17%">
                            <img src="{{ $ayam('mata1.webp') }}" alt="" draggable="false">
                            <span class="ayam-kilau" style="-webkit-mask-image:url('{{ $ayam('mata1.webp') }}');mask-image:url('{{ $ayam('mata1.webp') }}')">
                                <i class="ayam-sorot" style="left:35.38%;top:12.21%;width:33.63%;height:27%;--r:9deg"></i>
                                <i class="ayam-sorot ayam-sorot-kecil" style="left:63%;top:66%;width:14%;height:9%;--r:-35deg"></i>
                                <i class="ayam-teduh"></i>
                            </span>
                            <span class="ayam-kulit" style="background-image:url('{{ $ayam('badan.webp') }}');background-size:987.1% 1394.7%;background-position:calc(70.38% + var(--ex, 0) * 0.11273%) calc(20.87% + var(--ey, 0) * 0.07724%);-webkit-mask-image:url('{{ $ayam('mata1_kulit.webp') }}');mask-image:url('{{ $ayam('mata1_kulit.webp') }}')"></span>
                            <i class="ayam-garis"></i>
                        </span>
                    </span>
                    <span class="ayam-mata ayam-mata-jauh">
                        <span class="ayam-oko" style="left:82.33%;top:20.06%;width:5.17%;height:5.49%">
                            <img src="{{ $ayam('mata2.webp') }}" alt="" draggable="false">
                            <span class="ayam-kilau" style="-webkit-mask-image:url('{{ $ayam('mata2.webp') }}');mask-image:url('{{ $ayam('mata2.webp') }}')">
                                <i class="ayam-sorot" style="left:23.86%;top:22.1%;width:53.35%;height:13.25%;--r:53.2deg"></i>
                                <i class="ayam-teduh"></i>
                            </span>
                            <span class="ayam-kulit" style="background-image:url('{{ $ayam('badan.webp') }}');background-size:1934% 1821%;background-position:calc(86.82% + var(--ex, 0) * 0.006543%) calc(21.22% + var(--ey, 0) * 0.010459%);-webkit-mask-image:url('{{ $ayam('mata2_kulit.webp') }}');mask-image:url('{{ $ayam('mata2_kulit.webp') }}')"></span>
                            <i class="ayam-garis ayam-garis-jauh"></i>
                        </span>
                    </span>
                </span>
            </span>
        </span>
    </button>
</div>
