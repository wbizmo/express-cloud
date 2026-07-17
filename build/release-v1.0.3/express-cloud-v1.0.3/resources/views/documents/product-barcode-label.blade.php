<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $product->name }} label</title>
<style>
@page { size: 50mm 30mm; margin: 2mm; }
body { width:46mm; height:26mm; margin:0; text-align:center; font-family:Arial,sans-serif; font-size:8px; overflow:hidden; }
img { width:17mm; height:17mm; }
.name { font-size:9px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.code { font-family:monospace; letter-spacing:1px; }
</style>
</head>
<body>
<div class="name">{{ $product->name }}</div>
<img src="{{ $codeDataUri }}" alt="">
<div class="code">{{ $payload }}</div>
<div>₦{{ number_format($product->default_price_kobo / 100, 2) }}</div>
</body>
</html>
