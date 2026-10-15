@props(['narrow' => false])

{{-- Contenedor único de la carcasa: la barra superior y el contenido comparten la misma
     columna, de modo que título y botones quedan alineados con las tarjetas.

     Modo por defecto (pantallas de datos): arranca pegado al sidebar y ocupa todo el
     ancho disponible; solo en pantallas muy grandes se limita (1800px) y se centra, para
     que en ultrawide no se estire de forma absurda. Padding horizontal: 20px en móvil,
     40px desde lg.

     Variante `narrow` (formularios y ajustes): estrecha y centrada, máx. 720px. --}}
<div {{ $attributes->merge(['class' => 'w-full px-5 lg:px-10 mx-auto '.($narrow ? 'max-w-[720px]' : 'max-w-[1800px]')]) }}>
    {{ $slot }}
</div>