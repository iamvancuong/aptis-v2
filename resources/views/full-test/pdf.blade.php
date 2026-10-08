{{-- PDF bảng điểm Full Test (dompdf). Font giống vocabulary/pdf.blade.php. --}}
@php
    $fontDir = 'file://' . str_replace('\\', '/', resource_path('fonts/be-vietnam-pro'));
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Bảng điểm Full Test {{ $fullTest->code() }}</title>
<style>
    @foreach([400, 700] as $w)
        @font-face { font-family: 'BVP'; font-weight: {{ $w }}; src: url('{{ $fontDir }}/be-vietnam-pro-latin-{{ $w }}-normal.ttf') format('truetype'); }
        @font-face { font-family: 'BVP VI'; font-weight: {{ $w }}; src: url('{{ $fontDir }}/be-vietnam-pro-vietnamese-{{ $w }}-normal.ttf') format('truetype'); }
    @endforeach
    @page { margin: 14mm 14mm 14mm 14mm; }
    * { font-family: 'BVP', 'BVP VI', 'DejaVu Sans', sans-serif; }
    body { margin: 0; }
</style>
</head>
<body>
    @include('full-test._certificate')
</body>
</html>
